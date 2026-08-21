<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
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
        $templatePath = dirname(__DIR__, 2) . '/samples/templates/template_01_simple_variables.odt';

        self::assertFileExists($templatePath);

        $template = new OdtTemplate($templatePath);
        $template->load();
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

        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->outputFile) === true);

        try {
            foreach (['mimetype', 'content.xml', 'styles.xml', 'meta.xml', 'META-INF/manifest.xml'] as $entry) {
                self::assertNotFalse($zip->locateName($entry), sprintf('Missing ODT package entry: %s', $entry));
            }

            self::assertSame(
                'application/vnd.oasis.opendocument.text',
                $zip->getFromName('mimetype')
            );

            $contentXml = $zip->getFromName('content.xml');
            $stylesXml = $zip->getFromName('styles.xml');
            $metaXml = $zip->getFromName('meta.xml');

            self::assertIsString($contentXml);
            self::assertIsString($stylesXml);
            self::assertIsString($metaXml);

            self::assertStringContainsString('Integration Test', $contentXml);
            self::assertStringContainsString('Coffee', $contentXml);
            self::assertStringContainsString('Tea', $contentXml);
            self::assertStringNotContainsString('{{name}}', $contentXml);

            $this->assertWellFormedXml($contentXml, 'content.xml');
            $this->assertWellFormedXml($stylesXml, 'styles.xml');
            $this->assertWellFormedXml($metaXml, 'meta.xml');
        } finally {
            $zip->close();
        }
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
