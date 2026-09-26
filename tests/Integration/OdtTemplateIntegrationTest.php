<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
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

    public function testCanonicalMetadataKeywordsCoverageAndCreatorRoundTrip(): void
    {
        $template = new OdtTemplate($this->templatePath('template_04_metadata.odt'));
        $template->setMeta([
            'creator' => 'Current Creator',
            'initial_creator' => 'Original Creator',
            'keywords' => ['finance', 'report', '2026'],
            'coverage' => 'Extended geographic and temporal coverage',
            'language' => 'en-US',
            'creation_date' => '2026-09-18T10:30:00Z',
            'date' => '2026-09-18T10:31:00+02:00',
            'editing_cycles' => 0,
            'editing_duration' => 'P1Y2M3DT4H5M6.5S',
        ]);
        $template->save($this->outputFile);

        $this->withArchive(function (ZipArchive $zip): void {
            $metaXml = $this->readEntry($zip, 'meta.xml');
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($metaXml));
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('meta', 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0');
            $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');

            $keywordNodes = $xpath->query('//meta:keyword');
            self::assertNotFalse($keywordNodes);
            self::assertSame(3, $keywordNodes->length);
            self::assertSame(['finance', 'report', '2026'], array_map(
                static fn ($node): string => $node->textContent,
                iterator_to_array($keywordNodes)
            ));
            self::assertSame('Current Creator', $xpath->evaluate('string(//dc:creator)'));
            self::assertSame('Original Creator', $xpath->evaluate('string(//meta:initial-creator)'));
            self::assertSame('Extended geographic and temporal coverage', $xpath->evaluate('string(//dc:coverage)'));
            self::assertSame('Demonstration of setting and displaying metadata', $xpath->evaluate('string(//dc:subject)'));
            self::assertSame('Walter Diezt', $xpath->evaluate('string(//dc:publisher)'));
            self::assertSame('https://github.com/WaltDietzney/odt-template-engine', $xpath->evaluate('string(//dc:source)'));
            self::assertSame(0, $xpath->query('//meta:not_a_supported_key')->length);
        });

        $reopened = new OdtTemplate($this->outputFile);
        $metadata = $reopened->getMeta();
        self::assertSame('Current Creator', $metadata['creator'] ?? null);
        self::assertSame('Current Creator', $metadata['author'] ?? null);
        self::assertSame('Original Creator', $metadata['initial_creator'] ?? null);
        self::assertSame('Original Creator', $metadata['initial_author'] ?? null);
        self::assertSame(['finance', 'report', '2026'], $metadata['keywords'] ?? null);
        self::assertSame('Extended geographic and temporal coverage', $metadata['coverage'] ?? null);
        self::assertSame('en-US', $metadata['language'] ?? null);
        self::assertSame('2026-09-18T10:30:00Z', $metadata['creation_date'] ?? null);
        self::assertSame('2026-09-18T10:31:00+02:00', $metadata['date'] ?? null);
        self::assertSame('0', $metadata['editing_cycles'] ?? null);
        self::assertSame('P1Y2M3DT4H5M6.5S', $metadata['editing_duration'] ?? null);
    }

    public function testEmptyKeywordCollectionRemovesEveryKeywordElement(): void
    {
        $template = new OdtTemplate($this->templatePath('template_04_metadata.odt'));
        $template->setMeta(['keywords' => []]);
        $template->save($this->outputFile);

        $this->withArchive(function (ZipArchive $zip): void {
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($this->readEntry($zip, 'meta.xml')));
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('meta', 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0');
            self::assertSame(0, $xpath->query('//meta:keyword')->length);
        });
    }

    public function testSingleCanonicalKeywordWritesOneMetaKeywordElement(): void
    {
        $template = new OdtTemplate($this->templatePath('template_04_metadata.odt'));
        $template->setMeta(['keywords' => ['single']]);
        $template->save($this->outputFile);

        $this->withArchive(function (ZipArchive $zip): void {
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($this->readEntry($zip, 'meta.xml')));
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('meta', 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0');
            $keywords = $xpath->query('//meta:keyword');
            self::assertNotFalse($keywords);
            self::assertSame(1, $keywords->length);
            self::assertSame('single', $keywords->item(0)?->textContent);
        });
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
        $path = dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/' . $fileName;
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
