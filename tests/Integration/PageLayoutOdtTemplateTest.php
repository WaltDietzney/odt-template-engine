<?php

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class PageLayoutOdtTemplateTest extends TestCase
{
    public function testSetPageMarginsUpdatesStylesXml(): void
    {
        $template = new PageLayoutOdtTemplate('samples/templates/template_01_simple_variables.odt');
        $template->setPageMargins('0.5cm', '1cm', '1.5cm', '2cm');

        $output = sys_get_temp_dir() . '/odt-page-layout-' . uniqid('', true) . '.odt';
        $template->save($output);

        try {
            $properties = $this->loadPageLayoutProperties($output);

            self::assertSame('0.5cm', $properties['margin-top']);
            self::assertSame('1cm', $properties['margin-right']);
            self::assertSame('1.5cm', $properties['margin-bottom']);
            self::assertSame('2cm', $properties['margin-left']);
        } finally {
            @unlink($output);
        }
    }

    public function testSetPageLayoutCanChangeOrientationAndPageSize(): void
    {
        $template = new PageLayoutOdtTemplate('samples/templates/template_01_simple_variables.odt');
        $template->setPageLayout([
            'page-width' => '29.7cm',
            'page-height' => '21cm',
            'orientation' => 'landscape',
        ]);

        $output = sys_get_temp_dir() . '/odt-page-layout-' . uniqid('', true) . '.odt';
        $template->save($output);

        try {
            $properties = $this->loadPageLayoutProperties($output);

            self::assertSame('29.7cm', $properties['page-width']);
            self::assertSame('21cm', $properties['page-height']);
            self::assertSame('landscape', $properties['orientation']);
        } finally {
            @unlink($output);
        }
    }

    /**
     * @return array<string, string>
     */
    private function loadPageLayoutProperties(string $odtPath): array
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($odtPath) === true);

        $xml = $zip->getFromName('styles.xml');
        $zip->close();

        self::assertIsString($xml);

        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($xml));

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        $xpath->registerNamespace('fo', 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0');

        $node = $xpath->query('//style:page-layout/style:page-layout-properties')->item(0);
        self::assertNotNull($node);

        return [
            'margin-top' => $node->attributes->getNamedItemNS(
                'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                'margin-top'
            )?->nodeValue ?? '',
            'margin-right' => $node->attributes->getNamedItemNS(
                'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                'margin-right'
            )?->nodeValue ?? '',
            'margin-bottom' => $node->attributes->getNamedItemNS(
                'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                'margin-bottom'
            )?->nodeValue ?? '',
            'margin-left' => $node->attributes->getNamedItemNS(
                'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                'margin-left'
            )?->nodeValue ?? '',
            'page-width' => $node->attributes->getNamedItemNS(
                'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                'page-width'
            )?->nodeValue ?? '',
            'page-height' => $node->attributes->getNamedItemNS(
                'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                'page-height'
            )?->nodeValue ?? '',
            'orientation' => $node->attributes->getNamedItemNS(
                'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                'print-orientation'
            )?->nodeValue ?? '',
        ];
    }
}
