<?php

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class PageLayoutOdtTemplateTest extends TestCase
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const FO_NS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';

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

    public function testSetPageMarginsPreservesSetPageLayoutPolymorphism(): void
    {
        $template = new class ('samples/templates/template_01_simple_variables.odt') extends PageLayoutOdtTemplate {
            public bool $setPageLayoutCalled = false;

            public function setPageLayout(array $options, string $masterPage = 'Standard'): static
            {
                $this->setPageLayoutCalled = true;

                return parent::setPageLayout($options, $masterPage);
            }
        };

        $template->setPageMargins('0.5cm', '1cm', '1.5cm', '2cm');

        self::assertTrue($template->setPageLayoutCalled);
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
        $xpath->registerNamespace('style', self::STYLE_NS);
        $xpath->registerNamespace('fo', self::FO_NS);

        $masterPage = $xpath->query('//style:master-page[@style:name="Standard"]')->item(0);
        self::assertInstanceOf(DOMElement::class, $masterPage);

        $layoutName = $masterPage->getAttributeNS(self::STYLE_NS, 'page-layout-name');
        self::assertNotSame('', $layoutName);

        $node = $xpath->query(
            sprintf('//style:page-layout[@style:name="%s"]/style:page-layout-properties', $layoutName)
        )->item(0);
        self::assertInstanceOf(DOMElement::class, $node);

        return [
            'margin-top' => $node->getAttributeNS(self::FO_NS, 'margin-top'),
            'margin-right' => $node->getAttributeNS(self::FO_NS, 'margin-right'),
            'margin-bottom' => $node->getAttributeNS(self::FO_NS, 'margin-bottom'),
            'margin-left' => $node->getAttributeNS(self::FO_NS, 'margin-left'),
            'page-width' => $node->getAttributeNS(self::FO_NS, 'page-width'),
            'page-height' => $node->getAttributeNS(self::FO_NS, 'page-height'),
            'orientation' => $node->getAttributeNS(self::STYLE_NS, 'print-orientation'),
        ];
    }
}
