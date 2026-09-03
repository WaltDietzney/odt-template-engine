<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementMaterializer;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleWriter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StyleRequirementFontCurrentBehaviorCharacterizationTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-font-current-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($this->temporaryDirectory);
    }

    public function testSemanticParagraphAndTextFontUsesStylesXmlFontFaceDeclaration(): void
    {
        $font = 'SR05A Single Font ' . bin2hex(random_bytes(3));
        $paragraphName = 'SR05A_Paragraph_' . bin2hex(random_bytes(3));
        $paragraph = (new Paragraph($paragraphName, ['font-family' => $font]))
            ->addText('fonted', ['font-family' => $font]);
        $textStyleName = array_key_first($paragraph->getRequiredStyles());
        self::assertIsString($textStyleName);
        $output = $this->save((new RichText())->addParagraph($paragraph));
        $styles = $this->xml($output, 'styles.xml');

        $paragraph = $this->style($styles, $paragraphName, 'paragraph');
        self::assertNotNull($paragraph);
        self::assertSame($font, $this->property($paragraph, 'text-properties')->getAttributeNS($this->namespace('style'), 'font-name'));
        self::assertSame($font, $this->property($paragraph, 'text-properties')->getAttributeNS($this->namespace('fo'), 'font-family'));

        $text = $this->style($styles, $textStyleName, 'text');
        self::assertNotNull($text);
        self::assertSame(1, $this->fontFaceCount($styles, $font));
        self::assertSame($font, $this->fontFace($styles, $font)->getAttributeNS($this->namespace('svg'), 'font-family'));
    }

    public function testMultipleSemanticStylesUsingSameFontProduceOneStylesXmlDeclaration(): void
    {
        $font = 'SR05A Shared Font ' . bin2hex(random_bytes(3));
        $richText = (new RichText())
            ->addParagraph(new Paragraph('SR05A_Shared_One_' . bin2hex(random_bytes(3)), ['font-family' => $font]))
            ->addParagraph(new Paragraph('SR05A_Shared_Two_' . bin2hex(random_bytes(3)), ['font-family' => $font]));

        $styles = $this->xml($this->save($richText), 'styles.xml');

        self::assertSame(1, $this->fontFaceCount($styles, $font));
    }

    public function testMultipleDifferentSemanticFontsProduceSeparateDeclarations(): void
    {
        $fontA = 'SR05A Font A ' . bin2hex(random_bytes(3));
        $fontB = 'SR05A Font B ' . bin2hex(random_bytes(3));
        $richText = (new RichText())
            ->addParagraph(new Paragraph('SR05A_Different_One_' . bin2hex(random_bytes(3)), ['font-family' => $fontA]))
            ->addParagraph(new Paragraph('SR05A_Different_Two_' . bin2hex(random_bytes(3)), ['font-family' => $fontB]));

        $styles = $this->xml($this->save($richText), 'styles.xml');

        self::assertSame(1, $this->fontFaceCount($styles, $fontA));
        self::assertSame(1, $this->fontFaceCount($styles, $fontB));
    }

    public function testNativeFontFaceIdentityAndFamilyAreCollapsedByCurrentWriter(): void
    {
        $identity = 'SR05A_NativeIdentity_' . bin2hex(random_bytes(3));
        $family = 'DejaVu Serif';
        $paragraph = (new Paragraph())->addText('native font', [
            'style:font-name' => $identity,
            'fo:font-family' => "'$family'",
        ]);
        $textStyleName = array_key_first($paragraph->getRequiredStyles());
        self::assertIsString($textStyleName);

        $styles = $this->xml($this->save((new RichText())->addParagraph($paragraph)), 'styles.xml');
        $textStyle = $this->style($styles, $textStyleName, 'text');
        self::assertNotNull($textStyle);
        self::assertSame($identity, $this->property($textStyle, 'text-properties')->getAttributeNS($this->namespace('style'), 'font-name'));
        self::assertSame("'$family'", $this->property($textStyle, 'text-properties')->getAttributeNS($this->namespace('fo'), 'font-family'));

        $fontFace = $this->fontFace($styles, $identity);
        self::assertSame($identity, $fontFace->getAttributeNS($this->namespace('style'), 'name'));
        self::assertSame($identity, $fontFace->getAttributeNS($this->namespace('svg'), 'font-family'));
    }

    public function testExistingStylesXmlFontFaceIsPreservedWithoutDuplication(): void
    {
        $identity = 'SR05A Existing Identity';
        $dom = $this->dom(
            '<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" '
            . 'xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0">'
            . '<office:font-face-decls><style:font-face style:name="SR05A Existing Identity" svg:font-family="DejaVu Serif"/></office:font-face-decls>'
            . '<office:styles/></office:document-styles>'
        );
        $context = $this->context($this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'), $dom);
        (new StyleRequirementMaterializer())->materialize($context, new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'text',
            StyleRequirement::PART_STYLES,
            'SR05A_Existing_Text',
            'Standard',
            ['style:text-properties' => ['style:font-name' => $identity]]
        ));

        StyleWriter::writeAllStyles($dom, false, false, false);

        $fontFace = $this->fontFace($dom, $identity);
        self::assertSame(1, $this->fontFaceCount($dom, $identity));
        self::assertSame('DejaVu Serif', $fontFace->getAttributeNS($this->namespace('svg'), 'font-family'));
    }

    public function testCurrentSemanticFontFaceDiscoveryIsStylesXmlOnly(): void
    {
        $font = 'SR05A Content Font';
        $content = $this->dom('<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><office:automatic-styles/></office:document-content>');
        $styles = $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><office:styles/></office:document-styles>');
        $context = $this->context($content, $styles);
        (new StyleRequirementMaterializer())->materialize($context, new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_AUTOMATIC,
            'text',
            StyleRequirement::PART_CONTENT,
            'SR05A_Content_Text',
            null,
            ['style:text-properties' => ['style:font-name' => $font]]
        ));

        StyleWriter::writeAllStyles($styles, false, false, false);

        self::assertSame(0, $this->fontFaceCount($content, $font));
        self::assertSame(0, $this->fontFaceCount($styles, $font));
    }

    #[RunInSeparateProcess]
    public function testSpecializedLegacyWriterFontStateIsProcessGlobalAcrossDocuments(): void
    {
        $styleName = 'SR05A_Legacy_Text_' . bin2hex(random_bytes(3));
        $font = 'SR05A Legacy Font ' . bin2hex(random_bytes(3));
        StyleMapper::setTextStyle($styleName, ['style:font-name' => $font]);
        ini_set('error_log', '/dev/null');

        $first = $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"/>');
        StyleWriter::writeTextStyles($first);
        StyleWriter::writeFontFaces($first);

        $second = $this->dom('<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"/>');
        StyleWriter::writeTextStyles($second);
        StyleWriter::writeFontFaces($second);

        self::assertSame(1, $first->getElementsByTagName('style:style')->length);
        self::assertSame(0, $second->getElementsByTagName('style:style')->length);
        self::assertSame(1, $second->getElementsByTagName('style:font-face')->length);
    }

    private function save(RichText $richText): string
    {
        $output = $this->temporaryDirectory . '/document-' . bin2hex(random_bytes(3)) . '.odt';
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/template_18_ListStyles.odt');
        $template->setElement('my_list', $richText);
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

    private function context(DOMDocument $content, DOMDocument $styles): OdtDocumentContext
    {
        return new OdtDocumentContext(
            $content,
            $styles,
            $this->dom('<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>')
        );
    }

    private function dom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML($xml));
        return $dom;
    }

    private function style(DOMDocument $dom, string $name, string $family): ?\DOMElement
    {
        $xpath = $this->xpath($dom);
        $nodes = $xpath->query(sprintf('//style:style[@style:name=%s and @style:family=%s]', $this->literal($name), $this->literal($family)));
        $style = $nodes?->item(0);
        return $style instanceof \DOMElement ? $style : null;
    }

    private function property(\DOMElement $style, string $localName): \DOMElement
    {
        foreach ($style->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->localName === $localName) {
                return $child;
            }
        }
        self::fail('Missing style property group: ' . $localName);
    }

    private function fontFaceCount(DOMDocument $dom, string $name): int
    {
        return $this->xpath($dom)->query(sprintf('//style:font-face[@style:name=%s]', $this->literal($name)))->length;
    }

    private function fontFace(DOMDocument $dom, string $name): \DOMElement
    {
        $node = $this->xpath($dom)->query(sprintf('//style:font-face[@style:name=%s]', $this->literal($name)))->item(0);
        self::assertInstanceOf(\DOMElement::class, $node);
        return $node;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', $this->namespace('office'));
        $xpath->registerNamespace('style', $this->namespace('style'));
        $xpath->registerNamespace('svg', $this->namespace('svg'));
        $xpath->registerNamespace('fo', $this->namespace('fo'));
        return $xpath;
    }

    private function namespace(string $prefix): string
    {
        return match ($prefix) {
            'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
            'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
        };
    }

    private function literal(string $value): string
    {
        return "'" . str_replace("'", "&apos;", $value) . "'";
    }

}
