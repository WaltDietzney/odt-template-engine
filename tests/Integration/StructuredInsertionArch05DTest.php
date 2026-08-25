<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class StructuredInsertionArch05DTest extends TestCase
{
    /** @var list<string> */
    private array $outputFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->outputFiles as $outputFile) {
            if (is_file($outputFile)) {
                unlink($outputFile);
            }
        }
    }

    public function testParagraphInsertionReplacesTheStructuredPlaceholder(): void
    {
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement(
            'my_list',
            (new Paragraph())->addText('Inserted paragraph', ['bold' => true])
        );

        $output = $this->saveTemplate($template, 'paragraph');
        $content = $this->readXmlEntry($output, 'content.xml');

        $xpath = $this->xpath($content);
        $paragraphs = $xpath->query('//text:p[normalize-space(.)="Inserted paragraph"]');

        self::assertNotFalse($paragraphs);
        self::assertCount(1, $paragraphs);
        self::assertStringNotContainsString('{{my_list}}', $content);
    }

    public function testRichTextFragmentInsertsMultipleSiblingParagraphs(): void
    {
        $richText = new RichText();
        $richText->addParagraph('First inserted paragraph');
        $richText->addParagraph('Second inserted paragraph');

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', $richText);

        $output = $this->saveTemplate($template, 'richtext-fragment');
        $content = $this->readXmlEntry($output, 'content.xml');
        $xpath = $this->xpath($content);

        self::assertCount(
            1,
            $xpath->query('//text:p[normalize-space(.)="First inserted paragraph"]')
        );
        self::assertCount(
            1,
            $xpath->query('//text:p[normalize-space(.)="Second inserted paragraph"]')
        );
        self::assertStringNotContainsString('{{my_list}}', $content);
    }

    public function testRichTableInsertionPreservesTableStructureAndName(): void
    {
        $table = (new RichTable())
            ->setTableName('Arch05DTable')
            ->addRow([
                new RichTableCell('First cell'),
                new RichTableCell('Second cell'),
            ])
            ->addRow([
                new RichTableCell('Third cell'),
                new RichTableCell('Fourth cell'),
            ]);

        $template = new OdtTemplate($this->templatePath('template_15_simpleTableStyled.odt'));
        $template->setElement('tableblock', $table);

        $output = $this->saveTemplate($template, 'table');
        $content = $this->readXmlEntry($output, 'content.xml');
        $xpath = $this->xpath($content);

        $tables = $xpath->query('//table:table[@table:name="Arch05DTable"]');
        self::assertNotFalse($tables);
        self::assertCount(1, $tables);
        self::assertCount(2, $xpath->query('//table:table[@table:name="Arch05DTable"]//table:table-row'));
        self::assertCount(4, $xpath->query('//table:table[@table:name="Arch05DTable"]//table:table-cell'));
        self::assertStringContainsString('First cell', $content);
        self::assertStringContainsString('Fourth cell', $content);
    }

    public function testImageElementInsertionCreatesFrameResourceAndManifestEntry(): void
    {
        $imagePath = dirname(__DIR__, 2) . '/assets/banner.png';
        $image = new ImageElement($imagePath, [
            'width' => '6cm',
            'anchor' => 'as-char',
        ]);

        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->setElement('my_list', (new RichText())->addImage($image));

        $output = $this->saveTemplate($template, 'image-element');
        $content = $this->readXmlEntry($output, 'content.xml');
        $manifest = $this->readXmlEntry($output, 'META-INF/manifest.xml');
        $xpath = $this->xpath($content);

        self::assertCount(1, $xpath->query('//draw:frame/draw:image[@xlink:href="Pictures/banner.png"]'));
        self::assertStringContainsString('svg:width="6cm"', $content);
        self::assertStringContainsString('Pictures/banner.png', $manifest);
        self::assertTrue($this->archiveContains($output, 'Pictures/banner.png'));
    }

    public function testAssignWithStructuredValueUsesTheExistingRenderInsertionPath(): void
    {
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));
        $template->assign([
            'my_list' => (new RichText())->addParagraph('Assigned structured value'),
        ]);
        $template->render();

        $output = $this->saveTemplate($template, 'assigned-element');
        $content = $this->readXmlEntry($output, 'content.xml');

        self::assertStringContainsString('Assigned structured value', $content);
        self::assertStringNotContainsString('{{my_list}}', $content);
    }

    public function testStructuredPlaceholderInStylesXmlIsReplaced(): void
    {
        $template = new OdtTemplate($this->templatePath('template_01_simple_variables.odt'));
        $template->setElement('foto', (new Paragraph())->addText('Header structured content'));

        $output = $this->saveTemplate($template, 'styles-placeholder');
        $styles = $this->readXmlEntry($output, 'styles.xml');

        self::assertStringContainsString('Header structured content', $styles);
        self::assertStringNotContainsString('{{foto}}', $styles);
        $this->assertWellFormedXml($styles, 'styles.xml');
    }

    public function testInlineCompatibleReplacementStaysInsideTheContainingParagraph(): void
    {
        $template = new StructuredInsertionInspectableTemplate(
            $this->templatePath('template_18_ListStyles.odt')
        );
        $dom = new DOMDocument('1.0', 'UTF-8');
        $paragraph = $dom->createElement('text:p');
        $paragraph->appendChild($dom->createTextNode('Before {{token}} after'));
        $dom->appendChild($paragraph);

        $replacement = $dom->createElement('text:span');
        $replacement->appendChild($dom->createTextNode('inline'));
        $template->replacePlaceholder($dom, 'token', $replacement);

        self::assertSame('text:p', $paragraph->nodeName);
        self::assertSame('Before ', $paragraph->childNodes->item(0)?->nodeValue);
        self::assertSame('text:span', $paragraph->childNodes->item(1)?->nodeName);
        self::assertSame('inline', $paragraph->childNodes->item(1)?->textContent);
        self::assertSame(' after', $paragraph->childNodes->item(2)?->nodeValue);
    }

    public function testNamedImageReplacementTargetsTheFrameAndReplacesNestedImage(): void
    {
        $template = new OdtTemplate($this->templatePath('template_05_replaceImage.odt'));
        $template->replaceImageByName('Logo', $this->imagePath());

        $output = $this->saveTemplate($template, 'named-image-defaults');
        $styles = $this->readXmlEntry($output, 'styles.xml');
        $xpath = $this->xpath($styles);
        $frames = $xpath->query('//draw:frame[@draw:name="Logo"]');

        self::assertNotFalse($frames);
        self::assertCount(1, $frames);
        $frame = $frames->item(0);
        self::assertInstanceOf(DOMElement::class, $frame);
        self::assertSame('char', $frame->getAttribute('text:anchor-type'));
        self::assertSame('Mfr1', $frame->getAttribute('draw:style-name'));
        self::assertSame('0', $frame->getAttribute('draw:z-index'));
        self::assertSame('5cm', $frame->getAttribute('svg:width'));
        self::assertSame('3cm', $frame->getAttribute('svg:height'));
        self::assertCount(1, $xpath->query('//draw:frame[@draw:name="Logo"]/draw:image[@xlink:href="Pictures/WaltDietzney.png"]'));
        self::assertStringContainsString('Pictures/WaltDietzney.png', $this->readXmlEntry($output, 'META-INF/manifest.xml'));
    }

    public function testNamedImageReplacementKeepsLegacyExplicitDimensionBehavior(): void
    {
        $template = new OdtTemplate($this->templatePath('template_05_replaceImage.odt'));
        $template->replaceImageByName('Logo', $this->imagePath(), ['width' => '6cm']);

        $output = $this->saveTemplate($template, 'named-image-width');
        $xpath = $this->xpath($this->readXmlEntry($output, 'styles.xml'));
        $frame = $xpath->query('//draw:frame[@draw:name="Logo"]')->item(0);

        self::assertNotNull($frame);
        self::assertSame('6cm', $frame->getAttribute('svg:width'));
        self::assertSame('3cm', $frame->getAttribute('svg:height'));
    }

    public function testNamedImageReplacementUsesBothExplicitDimensionsVerbatim(): void
    {
        $template = new OdtTemplate($this->templatePath('template_05_replaceImage.odt'));
        $template->replaceImageByName('Logo', $this->imagePath(), [
            'width' => '7cm',
            'height' => '2cm',
        ]);

        $output = $this->saveTemplate($template, 'named-image-both-dimensions');
        $xpath = $this->xpath($this->readXmlEntry($output, 'styles.xml'));
        $frame = $xpath->query('//draw:frame[@draw:name="Logo"]')->item(0);

        self::assertNotNull($frame);
        self::assertSame('7cm', $frame->getAttribute('svg:width'));
        self::assertSame('2cm', $frame->getAttribute('svg:height'));
    }

    public function testNamedImageReplacementWithMissingNameIsAResourceSideEffectingNoOp(): void
    {
        $template = new OdtTemplate($this->templatePath('template_05_replaceImage.odt'));
        $template->replaceImageByName('DoesNotExist', $this->imagePath());

        $output = $this->saveTemplate($template, 'named-image-missing');
        $styles = $this->readXmlEntry($output, 'styles.xml');
        $manifest = $this->readXmlEntry($output, 'META-INF/manifest.xml');

        self::assertStringNotContainsString('Pictures/WaltDietzney.png', $styles);
        self::assertStringContainsString('Pictures/WaltDietzney.png', $manifest);
        self::assertTrue($this->archiveContains($output, 'Pictures/WaltDietzney.png'));
    }

    public function testScalarRenderingInsideExistingTextBoxReplacesItsTextNode(): void
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $template->assign(['test1' => 'Text-box value']);
        $template->render();

        $output = $this->saveTemplate($template, 'textbox');
        $content = $this->readXmlEntry($output, 'content.xml');
        $xpath = $this->xpath($content);

        self::assertCount(1, $xpath->query('//draw:text-box//text:p[normalize-space(.)="Text-box value"]'));
        self::assertStringNotContainsString('{{test1}}', $content);
    }

    public function testStructuredInsertionInsideExistingTextBoxUsesItsSpecialBlockPath(): void
    {
        $template = new OdtTemplate($this->templatePath('sample_textfeld.odt'));
        $template->setElement('test1', (new Paragraph())->addText('Structured text-box value'));

        $output = $this->saveTemplate($template, 'textbox-structured');
        $content = $this->readXmlEntry($output, 'content.xml');
        $xpath = $this->xpath($content);

        self::assertCount(
            1,
            $xpath->query('//draw:text-box//text:p[normalize-space(.)="Structured text-box value"]')
        );
        self::assertStringNotContainsString('{{test1}}', $content);
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function imagePath(): string
    {
        $path = dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
        self::assertFileExists($path);

        return $path;
    }

    private function saveTemplate(OdtTemplate $template, string $suffix): string
    {
        $output = sys_get_temp_dir() . '/odt-arch05d-' . $suffix . '-' . uniqid('', true) . '.odt';
        $this->outputFiles[] = $output;
        $template->save($output);

        return $output;
    }

    private function readXmlEntry(string $archivePath, string $entry): string
    {
        $zip = $this->openArchive($archivePath);

        try {
            $xml = $zip->getFromName($entry);
            self::assertIsString($xml, sprintf('Missing ODT entry: %s', $entry));

            return $xml;
        } finally {
            $zip->close();
        }
    }

    private function openArchive(string $archivePath): ZipArchive
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($archivePath) === true);

        return $zip;
    }

    private function archiveContains(string $archivePath, string $entry): bool
    {
        $zip = $this->openArchive($archivePath);

        try {
            return $zip->locateName($entry) !== false;
        } finally {
            $zip->close();
        }
    }

    private function xpath(string $xml): DOMXPath
    {
        $dom = new DOMDocument();
        $this->assertWellFormedXml($xml, 'ODT XML');
        self::assertTrue($dom->loadXML($xml));

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $xpath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        return $xpath;
    }

    private function assertWellFormedXml(string $xml, string $label): void
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            self::assertTrue($dom->loadXML($xml), sprintf('%s must be well formed.', $label));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}

final class StructuredInsertionInspectableTemplate extends OdtTemplate
{
    public function replacePlaceholder(DOMDocument $dom, string $key, DOMNode $replacement): void
    {
        $this->replacePlaceholderWithDom($dom, $key, $replacement);
    }
}
