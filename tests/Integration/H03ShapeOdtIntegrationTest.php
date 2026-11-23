<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class H03ShapeOdtIntegrationTest extends TestCase
{
    public function testRepeatedSaveKeepsShapeAndFillDeclarationStable(): void
    {
        $first = tempnam(sys_get_temp_dir(), 'h03-repeat-a-');
        $second = tempnam(sys_get_temp_dir(), 'h03-repeat-b-');
        self::assertIsString($first);
        self::assertIsString($second);
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/sample_textfeld.odt');
        try {
            $template->setElement('test1', new ShapeElement(
                ShapeGeometry::preset('ellipse'),
                ShapeAppearance::bitmap(dirname(__DIR__, 2) . '/assets/WaltDietzney.png'),
                DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '3cm', 'height' => '3cm'])
            ));
            $template->save($first);
            $template->save($second);
            foreach ([$first, $second] as $output) {
                $zip = new ZipArchive();
                self::assertTrue($zip->open($output) === true);
                try {
                    $styles = $zip->getFromName('styles.xml');
                    self::assertIsString($styles);
                    $dom = new DOMDocument();
                    self::assertTrue($dom->loadXML($styles));
                    $xpath = new DOMXPath($dom);
                    $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
                    self::assertSame(1, $xpath->query('//draw:fill-image')->length);
                } finally {
                    $zip->close();
                }
            }
        } finally {
            $template->cleanup();
            unlink($first);
            unlink($second);
        }
    }

    public function testBitmapEllipseIsPackagedWithNativeStyleAndManifest(): void
    {
        $output = tempnam(sys_get_temp_dir(), 'h03-shape-');
        self::assertIsString($output);
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/sample_textfeld.odt');
        try {
            $shape = new ShapeElement(
                ShapeGeometry::preset('ellipse'),
                ShapeAppearance::bitmap(dirname(__DIR__, 2) . '/assets/WaltDietzney.png'),
                DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '3cm', 'height' => '3cm'])
            );
            $template->setElement('test1', $shape);
            $template->save($output);
            $zip = new ZipArchive();
            self::assertTrue($zip->open($output) === true);
            try {
                self::assertNotFalse($zip->locateName('Pictures/WaltDietzney.png'));
                $manifest = $zip->getFromName('META-INF/manifest.xml');
                self::assertIsString($manifest);
                self::assertStringContainsString('Pictures/WaltDietzney.png', $manifest);
                $content = $zip->getFromName('content.xml');
                $styles = $zip->getFromName('styles.xml');
                self::assertIsString($content);
                self::assertIsString($styles);
                $contentDom = new DOMDocument();
                self::assertTrue($contentDom->loadXML($content));
                $contentXPath = new DOMXPath($contentDom);
                $contentXPath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
                self::assertSame(1, $contentXPath->query('//draw:custom-shape[draw:enhanced-geometry]')->length);
                $stylesDom = new DOMDocument();
                self::assertTrue($stylesDom->loadXML($styles));
                $stylesXPath = new DOMXPath($stylesDom);
                $stylesXPath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
                self::assertSame(1, $stylesXPath->query('//draw:fill-image')->length);
                self::assertStringContainsString('draw:fill-image-name', $content);
                self::assertStringContainsString('draw:fill="bitmap"', $content);
                $contentXPath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
                self::assertSame(1, $contentXPath->query(
                    '//style:style[@style:family="graphic"][style:graphic-properties[@draw:fill="bitmap"]]'
                )->length);
                self::assertSame(0, $contentXPath->query(
                    '//style:style[@style:family="graphic"][style:graphic-properties[@draw:fill="bitmap"]]/@style:parent-style-name'
                )->length);
            } finally {
                $zip->close();
            }
        } finally {
            $template->cleanup();
            unlink($output);
        }
    }
}
