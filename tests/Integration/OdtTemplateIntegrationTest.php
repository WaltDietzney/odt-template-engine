<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\ListElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class OdtTemplateIntegrationTest extends TestCase
{
    private string $outputFile;

    protected function setUp(): void
    {
        $this->outputFile = sys_get_temp_dir() . '/odt-template-engine-' . uniqid('', true) . '.odt';
    }

    protected function tearDown(): void
    {
        if (is_file($this->outputFile)) {
            unlink($this->outputFile);
        }
    }

    public function testTemplateCanBeRenderedIntoValidOdtPackage(): void
    {
        $template = new OdtTemplate($this->templatePath('template_01_simple_variables.odt'));
        $template->assign([
            'name' => 'Integration Test',
            'datum' => '2026-08-21',
        ]);
        $template->assignRepeating('items', [
            ['produkt' => 'Coffee', 'preis' => '4.99'],
            ['produkt' => 'Tea', 'preis' => '3.49'],
        ]);
        $template->render();
        $template->save($this->outputFile);

        self::assertFileExists($this->outputFile);
        self::assertGreaterThan(0, filesize($this->outputFile));

        $this->withArchive(function (ZipArchive $zip): void {
            foreach (['mimetype', 'content.xml', 'styles.xml', 'meta.xml', 'META-INF/manifest.xml'] as $entry) {
                self::assertNotFalse($zip->locateName($entry), sprintf('Missing ODT package entry: %s', $entry));
            }

            self::assertSame(
                'application/vnd.oasis.opendocument.text',
                $zip->getFromName('mimetype')
            );

            $contentXml = $this->readEntry($zip, 'content.xml');
            $stylesXml = $this->readEntry($zip, 'styles.xml');
            $metaXml = $this->readEntry($zip, 'meta.xml');

            self::assertStringContainsString('Integration Test', $contentXml);
            self::assertStringContainsString('Coffee', $contentXml);
            self::assertStringContainsString('Tea', $contentXml);
            self::assertStringNotContainsString('{{name}}', $contentXml);

            $this->assertWellFormedXml($contentXml, 'content.xml');
            $this->assertWellFormedXml($stylesXml, 'styles.xml');
            $this->assertWellFormedXml($metaXml, 'meta.xml');
        });
    }

    public function testMetadataIsPersistedInMetaXml(): void
    {
        $template = new OdtTemplate($this->templatePath('template_04_metadata.odt'));
        $template->setMeta([
            'title' => 'Integration Metadata',
            'author' => 'ODT Test Suite',
            'subject' => 'Metadata persistence',
            'description' => 'Generated during integration testing.',
            'keywords' => 'odt,integration,metadata',
            'language' => 'en',
            'generator' => 'OdtTemplateEngine PHPUnit',
            'editing_cycles' => 2,
            'editing_duration' => 'PT5M',
            'date' => '2026-08-21T20:00:00+00:00',
        ]);
        $template->save($this->outputFile);

        $this->withArchive(function (ZipArchive $zip): void {
            $metaXml = $this->readEntry($zip, 'meta.xml');

            self::assertStringContainsString('Integration Metadata', $metaXml);
            self::assertStringContainsString('ODT Test Suite', $metaXml);
            self::assertStringContainsString('Metadata persistence', $metaXml);
            self::assertStringContainsString('OdtTemplateEngine PHPUnit', $metaXml);
            $this->assertWellFormedXml($metaXml, 'meta.xml');
        });

        $reloaded = new OdtTemplate($this->outputFile);
        $metadata = $reloaded->getMeta();

        self::assertSame('Integration Metadata', $metadata['title'] ?? null);
        self::assertSame('ODT Test Suite', $metadata['author'] ?? null);
        self::assertSame('en', $metadata['language'] ?? null);
    }

    public function testImageInsertionUpdatesContentAndManifest(): void
    {
        $imagePath = dirname(__DIR__, 2) . '/assets/banner.png';
        self::assertFileExists($imagePath);

        $template = new OdtTemplate($this->templatePath('template_06_imageSettings.odt'));
        $template->setImage('image', $imagePath, [
            'width' => '6cm',
            'anchor' => 'paragraph',
            'wrap' => 'none',
        ]);
        $template->save($this->outputFile);

        $this->withArchive(function (ZipArchive $zip): void {
            self::assertNotFalse($zip->locateName('Pictures/banner.png'));

            $contentXml = $this->readEntry($zip, 'content.xml');
            $manifestXml = $this->readEntry($zip, 'META-INF/manifest.xml');

            self::assertStringContainsString('Pictures/banner.png', $contentXml);
            self::assertStringContainsString('svg:width="6cm"', $contentXml);
            self::assertStringContainsString('Pictures/banner.png', $manifestXml);
            self::assertStringContainsString('image/png', $manifestXml);
            self::assertStringNotContainsString('{{image}}', $contentXml);
        });
    }

    public function testRichTextAndNestedListsAreWrittenToContentXml(): void
    {
        $template = new OdtTemplate($this->templatePath('template_18_ListStyles.odt'));

        $list = new ListElement('numbered');
        $list->addItem((new Paragraph())->addText('Introduction'));

        $subList = new ListElement('bullet');
        $subList->addItem((new Paragraph())->addText('Goal'));
        $subList->addItem((new Paragraph())->addText('Benefit'));
        $list->addItem($subList);

        $list->addItem((new Paragraph())->addText('Conclusion', ['bold' => true]));

        $template->setElement('my_list', $list);
        $template->save($this->outputFile);

        $this->withArchive(function (ZipArchive $zip): void {
            $contentXml = $this->readEntry($zip, 'content.xml');

            self::assertStringContainsString('Introduction', $contentXml);
            self::assertStringContainsString('Goal', $contentXml);
            self::assertStringContainsString('Benefit', $contentXml);
            self::assertStringContainsString('Conclusion', $contentXml);
            self::assertStringContainsString('<text:list', $contentXml);
            self::assertStringNotContainsString('{{my_list}}', $contentXml);
            $this->assertWellFormedXml($contentXml, 'content.xml');
        });
    }

    public function testRichTableAndHtmlImportProduceStructuredOdtContent(): void
    {
        $tableTemplate = new OdtTemplate($this->templatePath('template_15_simpleTableStyled.odt'));
        $table = new RichTable();
        $table->addRow([
            new RichTableCell('Task', ['background' => '#ddeeff', 'text-align' => 'center']),
            new RichTableCell('Status', ['background' => '#ddeeff', 'text-align' => 'center']),
        ]);
        $table->addRow([
            new RichTableCell((new RichText())->addText('HTML Import', ['bold' => true])),
            new RichTableCell('Ready'),
        ]);
        $tableTemplate->setElement('tableblock', $table);
        $tableTemplate->save($this->outputFile);

        $this->withArchive(function (ZipArchive $zip): void {
            $contentXml = $this->readEntry($zip, 'content.xml');

            self::assertStringContainsString('<table:table', $contentXml);
            self::assertStringContainsString('HTML Import', $contentXml);
            self::assertStringContainsString('Ready', $contentXml);
            self::assertStringNotContainsString('{{tableblock}}', $contentXml);
            $this->assertWellFormedXml($contentXml, 'content.xml');
        });

        unlink($this->outputFile);
        $this->outputFile = sys_get_temp_dir() . '/odt-template-engine-html-' . uniqid('', true) . '.odt';

        $htmlTemplate = new OdtTemplate($this->templatePath('template_19_htmlTable.odt'));
        $html = '<h2>Team Overview</h2>'
            . '<p><strong>Legend:</strong> imported content</p>'
            . '<table><thead><tr><th>Name</th><th>Role</th></tr></thead>'
            . '<tbody><tr><td>Alice</td><td>Developer</td></tr></tbody></table>';

        $htmlTemplate->setElement('tableblock', HtmlImporter::fromHtml($html));
        $htmlTemplate->save($this->outputFile);

        $this->withArchive(function (ZipArchive $zip): void {
            $contentXml = $this->readEntry($zip, 'content.xml');

            self::assertStringContainsString('Team Overview', $contentXml);
            self::assertStringContainsString('Alice', $contentXml);
            self::assertStringContainsString('Developer', $contentXml);
            self::assertStringContainsString('<table:table', $contentXml);
            self::assertStringNotContainsString('{{tableblock}}', $contentXml);
            $this->assertWellFormedXml($contentXml, 'content.xml');
        });
    }

    private function templatePath(string $fileName): string
    {
        $path = dirname(__DIR__, 2) . '/samples/templates/' . $fileName;
        self::assertFileExists($path);

        return $path;
    }

    private function withArchive(callable $callback): void
    {
        self::assertFileExists($this->outputFile);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->outputFile) === true);

        try {
            $callback($zip);
        } finally {
            $zip->close();
        }
    }

    private function readEntry(ZipArchive $zip, string $entry): string
    {
        $content = $zip->getFromName($entry);
        self::assertIsString($content, sprintf('Unable to read ODT package entry: %s', $entry));

        return $content;
    }

    private function assertWellFormedXml(string $xml, string $fileName): void
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            self::assertTrue($dom->loadXML($xml), sprintf('%s must contain well-formed XML.', $fileName));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
