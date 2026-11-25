<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\DrawingGroup;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\PageLayoutOdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class PartialRingCompositionTest extends TestCase
{
    public function testZeroAndFullProgressComposeWithoutDegenerateOrReimplementedShapes(): void
    {
        foreach ([0 => 1, 1 => 2, 70 => 2, 99 => 2, 100 => 2] as $percent => $count) {
            $dom = new DOMDocument();
            $node = $this->ring('value-' . $percent, $percent)->toDomNode($dom);
            self::assertSame($count, $node->childNodes->length);
            self::assertSame('as-char', $node->getAttribute('text:anchor-type'));
            self::assertFalse($node->hasAttribute('svg:width'));
            foreach ($node->childNodes as $index => $shape) {
                self::assertFalse($shape->hasAttribute('text:anchor-type'));
                self::assertSame((string) $index, $shape->getAttribute('draw:z-index'));
                self::assertSame($index === 0 || $percent === 100 ? 'ring' : 'non-primitive', $shape->lastChild->getAttribute('draw:type'));
            }
        }
    }

    public function testBodyHeaderFooterLifecycleHasNativeGeometryAndActualPartStyles(): void
    {
        $out = sys_get_temp_dir() . '/odt-rings-' . bin2hex(random_bytes(6)) . '.odt';
        $repeat = $out . '.repeat.odt';
        $reloaded = $out . '.reload.odt';
        $template = new PageLayoutOdtTemplate(dirname(__DIR__, 2) . '/tests/Fixtures/LegacySamples/templates/template_15_simpleTableStyled.odt');
        try {
            $template->definePageLayout('RingLayout', ['page-width' => '21cm', 'page-height' => '29.7cm']);
            $template->definePageStyle('RingPage', 'RingLayout', [
                'header' => (new Paragraph())->addInlineElement($this->ring('header', 70)),
                'footer' => (new Paragraph())->addInlineElement($this->ring('footer', 99)),
            ]);
            $template->appendBody((new Paragraph())->addInlineElement($this->ring('body', 1)));
            $template->save($out);
            $template->save($repeat);
            $a = new ZipArchive();
            $b = new ZipArchive();
            $a->open($out);
            $b->open($repeat);
            foreach (['content.xml', 'styles.xml'] as $part) {
                self::assertSame($a->getFromName($part), $b->getFromName($part));
                $dom = new DOMDocument();
                $dom->loadXML($a->getFromName($part));
                $shapes = $dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'custom-shape');
                self::assertSame($part === 'content.xml' ? 2 : 4, $shapes->length);
                foreach ($shapes as $shape) {
                    self::assertStringStartsWith($part === 'content.xml' ? 'drawing_c_' : 'drawing_s_', $shape->getAttribute('draw:style-name'));
                }
                self::assertSame(0, $dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'image')->length);
            }
            $a->close();
            $b->close();
            $again = new PageLayoutOdtTemplate($out);
            try {
                $again->save($reloaded);
                $c = new ZipArchive();
                $c->open($reloaded);
                $a->open($out);
                foreach (['content.xml', 'styles.xml'] as $part) {
                    self::assertSame($a->getFromName($part), $c->getFromName($part));
                }
                $a->close();
                $c->close();
            } finally {
                $again->cleanup();
            }
        } finally {
            $template->cleanup();
            foreach ([$out, $repeat, $reloaded] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    private function ring(string $name, int $percent): DrawingGroup
    {
        $layout = DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '2cm', 'height' => '2cm']);
        $children = [new ShapeElement(ShapeGeometry::annularSector(360, 7000 / 10800), ShapeAppearance::solid('#DDDDDD'), $layout)];
        $geometry = ShapeGeometry::annularSector($percent / 100 * 360, 7000 / 10800);
        if ($geometry !== null) {
            $children[] = new ShapeElement($geometry, ShapeAppearance::solid('#337799'), $layout);
        }
        return new DrawingGroup($name, $children);
    }
}
