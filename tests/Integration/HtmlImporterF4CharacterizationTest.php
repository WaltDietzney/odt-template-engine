<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Import\HtmlImporter;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Utils\TemporaryAssetRegistry;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class HtmlImporterF4CharacterizationTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/odt-html-f4-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory, 0755, true);
    }

    protected function tearDown(): void
    {
        TemporaryAssetRegistry::cleanup();
        $this->removeDirectory($this->temporaryDirectory);
    }

    public function testSupportedHtmlBecomesNativeEditableStructuresIncludingSpans(): void
    {
        $html = <<<'HTML'
<h1>Project report</h1>
<p>A <strong>bold</strong> and <em>emphasized</em> paragraph<br>continues here with <a href="https://example.com/report">a link</a>.</p>
<blockquote>A quoted note.</blockquote>
<ul><li>First item</li><li>Second item</li></ul>
<table><thead><tr><th style="background-color:#27616d; color:#ffffff; padding:0.1cm;">Name</th><th>Role</th></tr></thead><tbody><tr><td colspan="2"><span style="color:#245d3c;">Anna</span></td></tr></tbody></table>
<pre>line one
  line two</pre>
HTML;
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/samples/templates/template_L08_html_import.odt');
        $template->setElement('imported_report', HtmlImporter::fromHtml($html));
        $output = $this->temporaryDirectory . '/imported.odt';
        $template->save($output);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($output) === true);
        try {
            $content = $zip->getFromName('content.xml');
            self::assertIsString($content);
            $dom = new DOMDocument();
            self::assertTrue($dom->loadXML($content));
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
            $xpath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
            $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

            self::assertGreaterThan(0, $xpath->query('//text:p[@text:style-name="Heading 1"]')->length);
            self::assertGreaterThan(0, $xpath->query('//text:span[@text:style-name]')->length);
            self::assertCount(1, $xpath->query('//text:a[@xlink:href="https://example.com/report"]'));
            self::assertGreaterThan(0, $xpath->query('//text:line-break')->length);
            self::assertGreaterThanOrEqual(1, $xpath->query('//text:list[@text:style-name="Bullet_20_Symbol"]')->length);
            self::assertCount(1, $xpath->query('//table:table'));
            self::assertCount(2, $xpath->query('//table:table-row'));
            self::assertCount(1, $xpath->query('//table:table-cell[@table:number-columns-spanned="2"]'));
            self::assertStringContainsString('line two', $content);
            self::assertStringNotContainsString('{{imported_report}}', $content);
        } finally {
            $zip->close();
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
