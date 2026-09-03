<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Document\FontFaceRequirement;
use OdtTemplateEngine\Document\FontFaceRequirementMaterializer;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleRequirementFontMaterializationTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-font-materialization-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($this->temporaryDirectory);
    }

    public function testSemanticIdentityAndFamilySurviveNormalSave(): void
    {
        $identity = 'SR05E Identity ' . bin2hex(random_bytes(3));
        $family = 'Liberation Sans';
        $paragraph = (new Paragraph())->addText('semantic font', [
            'style:font-name' => $identity,
            'fo:font-family' => "'$family'",
        ]);
        $output = $this->save((new RichText())->addParagraph($paragraph));
        $styles = $this->xml($output, 'styles.xml');
        $fontFace = $this->fontFace($styles, $identity);

        self::assertNotNull($fontFace);
        self::assertSame($identity, $fontFace->getAttributeNS($this->namespace('style'), 'name'));
        self::assertSame($family, $this->normalizeFamily($fontFace->getAttributeNS($this->namespace('svg'), 'font-family')));
        self::assertSame(1, $this->fontFaceCount($styles, $identity));
    }

    public function testMultipleSetElementsAreMaterializedTogetherWithoutDuplicates(): void
    {
        $fontA = 'SR05E Font A ' . bin2hex(random_bytes(3));
        $fontB = 'SR05E Font B ' . bin2hex(random_bytes(3));
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt');
        $template->setElement('my_list', (new RichText())->addParagraph((new Paragraph())->addText('A', ['font-family' => $fontA])));
        $template->setElement('my_list_second', (new RichText())->addParagraph((new Paragraph())->addText('B', ['font-family' => $fontB])));
        $first = $this->temporaryDirectory . '/first.odt';
        $second = $this->temporaryDirectory . '/second.odt';

        $template->save($first);
        $template->save($second);

        foreach ([$first, $second] as $output) {
            $styles = $this->xml($output, 'styles.xml');
            self::assertSame(1, $this->fontFaceCount($styles, $fontA));
            self::assertSame(1, $this->fontFaceCount($styles, $fontB));
        }
    }

    #[RunInSeparateProcess]
    public function testLegacyStyleWriterStillMaterializesNonSemanticCompatibilityFonts(): void
    {
        $style = 'SR05E_Legacy_' . bin2hex(random_bytes(3));
        $font = 'SR05E Legacy Font ' . bin2hex(random_bytes(3));
        StyleMapper::setTextStyle($style, ['style:font-name' => $font]);
        ini_set('error_log', '/dev/null');
        $dom = $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"/>');

        StyleWriter::writeTextStyles($dom);
        StyleWriter::writeFontFaces($dom);

        self::assertSame(1, $dom->getElementsByTagName('style:font-face')->length);
    }

    #[RunInSeparateProcess]
    public function testSemanticAndUnrelatedLegacyFontsRemainSeparatePhysicalResponsibilities(): void
    {
        $semanticIdentity = 'SR05E Semantic Identity ' . bin2hex(random_bytes(3));
        $semanticFamily = 'Semantic Family';
        $legacyStyle = 'SR05E_Mixed_Legacy_' . bin2hex(random_bytes(3));
        $legacyFont = 'SR05E Unrelated Legacy Font ' . bin2hex(random_bytes(3));
        $styles = $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"><office:styles/></office:document-styles>');
        $context = new OdtDocumentContext($this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'), $styles, $this->dom('<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'));
        $semantic = new FontFaceRequirement(FontFaceRequirement::PART_STYLES, $semanticIdentity, $semanticFamily);
        $context->registerFontFaceRequirement($semantic);
        StyleMapper::setTextStyle($legacyStyle, ['style:font-name' => $legacyFont]);
        ini_set('error_log', '/dev/null');

        (new FontFaceRequirementMaterializer())->materializeAll($context, $context->fontFaceRequirements()->requirements());
        StyleWriter::writeAllStyles($styles);

        self::assertSame(1, $this->fontFaceCount($styles, $semanticIdentity));
        self::assertStringContainsString('style:name="' . $legacyFont . '"', (string) $styles->saveXML());
        self::assertSame($semanticFamily, $this->fontFace($styles, $semanticIdentity)->getAttributeNS($this->namespace('svg'), 'font-family'));
    }

    private function save(RichText $element): string
    {
        $output = $this->temporaryDirectory . '/document-' . bin2hex(random_bytes(3)) . '.odt';
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt');
        $template->setElement('my_list', $element);
        $template->save($output);

        return $output;
    }

    private function xml(string $path, string $entry): DOMDocument
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        try {
            $xml = $zip->getFromName($entry);
            self::assertIsString($xml);
        } finally {
            $zip->close();
        }
        return $this->dom($xml);
    }

    private function fontFaceCount(DOMDocument $dom, string $identity): int
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', $this->namespace('office'));
        $xpath->registerNamespace('style', $this->namespace('style'));
        return $xpath->query('//office:font-face-decls/style:font-face[@style:name="' . $identity . '"]')->length;
    }

    private function fontFace(DOMDocument $dom, string $identity): \DOMElement
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', $this->namespace('office'));
        $xpath->registerNamespace('style', $this->namespace('style'));
        $node = $xpath->query('//office:font-face-decls/style:font-face[@style:name="' . $identity . '"]')->item(0);
        self::assertInstanceOf(\DOMElement::class, $node);
        return $node;
    }

    private function normalizeFamily(string $family): string
    {
        $family = trim($family);
        if (strlen($family) >= 2 && (($family[0] === "'" && $family[strlen($family) - 1] === "'") || ($family[0] === '"' && $family[strlen($family) - 1] === '"'))) {
            return trim(substr($family, 1, -1));
        }
        return $family;
    }

    private function namespace(string $prefix): string
    {
        return [
            'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
        ][$prefix];
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }
}
