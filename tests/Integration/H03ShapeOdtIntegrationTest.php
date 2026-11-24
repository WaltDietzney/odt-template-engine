<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMXPath;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\Elements\ShapeTextLayout;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class H03ShapeOdtIntegrationTest extends TestCase
{
    public function testNativeShapeTextStylesArePackaged(): void
    {
        $output = tempnam(sys_get_temp_dir(), 'h03-shape-text-');
        self::assertIsString($output);
        $template = new OdtTemplate(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/sample_textfeld.odt');
        try {
            $shape = new ShapeElement(
                ShapeGeometry::preset('rectangle'),
                ShapeAppearance::none('none'),
                DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '4cm', 'height' => '1cm'])
            );
            $shape->addParagraph((new Paragraph('Standard', ['text-align' => 'center']))
                ->addText('Teamarbeit', [
                    'color' => '#ffffff',
                    'font-size' => '12pt',
                    'font-family' => 'Liberation Sans',
                    'bold' => true,
                ]));
            $template->setElement('test1', $shape);
            $template->save($output);
            $zip = new ZipArchive();
            self::assertTrue($zip->open($output) === true);
            try {
                $content = $zip->getFromName('content.xml');
                $styles = $zip->getFromName('styles.xml');
                self::assertIsString($content);
                self::assertIsString($styles);
                self::assertStringContainsString('Teamarbeit', $content);
                self::assertStringContainsString('fo:color="#ffffff"', $styles);
                self::assertStringContainsString('fo:font-size="12pt"', $styles);
                self::assertStringContainsString('fo:font-family="Liberation Sans"', $styles);
            } finally {
                $zip->close();
            }
        } finally {
            $template->cleanup();
            unlink($output);
        }
    }

    public function testWriterReferenceTextPlacementAndFormattingRemainSeparate(): void
    {
        $cases = [
            ['left', 'top', '#ffffff', '10pt'],
            ['center', 'middle', '#ffe066', '12pt'],
            ['right', 'bottom', '#90e0ef', '14pt'],
        ];
        foreach ($cases as [$alignment, $vertical, $color, $size]) {
            $output = tempnam(sys_get_temp_dir(), 'h03-text-layout-');
            self::assertIsString($output);
            $template = new OdtTemplate(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/sample_textfeld.odt');
            try {
                $shape = new ShapeElement(
                    ShapeGeometry::preset('rectangle'),
                    ShapeAppearance::none('none'),
                    DrawingLayout::fromArray([
                        'anchor' => 'paragraph',
                        'width' => '3.5cm',
                        'height' => '0.7cm',
                        'horizontal' => ['offset' => '0.15cm', 'relative-to' => 'paragraph'],
                        'vertical' => ['offset' => '0.2cm', 'relative-to' => 'paragraph'],
                    ])
                );
                $shape->setTextLayout(new ShapeTextLayout($vertical, [
                    'left' => '0.1cm',
                    'right' => '0.1cm',
                    'top' => '0.02cm',
                    'bottom' => '0.02cm',
                ], $alignment));
                $shape->addParagraph((new Paragraph(null, ['text-align' => $alignment]))
                    ->addText('Skill Label', [
                        'color' => $color,
                        'font-size' => $size,
                        'font-family' => 'Liberation Sans',
                    ]));
                $template->setElement('test1', $shape);
                $template->save($output);
                $zip = new ZipArchive();
                self::assertTrue($zip->open($output) === true);
                try {
                    $content = $zip->getFromName('content.xml');
                    $styles = $zip->getFromName('styles.xml');
                    self::assertIsString($content);
                    self::assertIsString($styles);
                    self::assertStringContainsString('Skill Label', $content);
                    self::assertStringContainsString('draw:textarea-vertical-align="' . $vertical . '"', $content);
                    self::assertStringContainsString('fo:padding-left="0.1cm"', $content);
                    self::assertStringContainsString('draw:textarea-horizontal-align="' . $alignment . '"', $content);
                    self::assertStringContainsString('draw:text-style-name=', $content);
                    self::assertStringContainsString('fo:text-align="' . $alignment . '"', $styles);
                    self::assertStringContainsString('fo:color="' . $color . '"', $styles);
                    self::assertStringContainsString('fo:font-size="' . $size . '"', $styles);
                    self::assertStringContainsString('svg:x="0.15cm"', $content);
                    self::assertStringContainsString('svg:y="0.2cm"', $content);
                } finally {
                    $zip->close();
                }
            } finally {
                $template->cleanup();
                unlink($output);
            }
        }
    }

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
