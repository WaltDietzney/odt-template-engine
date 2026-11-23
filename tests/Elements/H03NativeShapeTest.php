<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use DOMDocument;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StructuredInsertionMode;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use PHPUnit\Framework\TestCase;

final class H03NativeShapeTest extends TestCase
{
    public function testSolidRectangleProducesNativeGeometryAndStyle(): void
    {
        $shape = new ShapeElement(
            ShapeGeometry::preset('rectangle'),
            ShapeAppearance::solid('#12ab34'),
            DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '4cm', 'height' => '2cm'])
        );
        $node = $shape->toDomNode(new DOMDocument());
        self::assertSame('draw:custom-shape', $node->nodeName);
        self::assertSame('4cm', $node->attributes->getNamedItem('svg:width')?->nodeValue);
        self::assertSame('rectangle', $node->firstChild?->attributes?->getNamedItem('draw:type')?->nodeValue);
        self::assertSame(StructuredInsertionMode::PRESERVE_TEXT_CONTAINER, $shape->structuredInsertionMode());
        $style = iterator_to_array($shape->getOwnStyleRequirements())[0];
        self::assertSame('#12AB34', $style->propertyGroups()['style:graphic-properties']['draw:fill-color']);
        self::assertSame([], iterator_to_array($shape->getOwnFillImageDependencies()));
    }

    public function testEllipseAndArrowAreNativeEditableContours(): void
    {
        foreach (['ellipse', 'arrow-contour'] as $type) {
            $shape = new ShapeElement(
                ShapeGeometry::preset($type),
                ShapeAppearance::none(),
                DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '3cm', 'height' => '3cm'])
            );
            $node = $shape->toDomNode(new DOMDocument());
            self::assertSame($type === 'arrow-contour' ? 'right-arrow' : $type, $node->firstChild?->attributes?->getNamedItem('draw:type')?->nodeValue);
            self::assertNotEmpty($node->firstChild?->attributes?->getNamedItem('draw:enhanced-path')?->nodeValue);
        }
    }

    public function testRejectsUnsupportedAnchor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ShapeElement(
            ShapeGeometry::preset('rectangle'),
            ShapeAppearance::none(),
            DrawingLayout::fromArray(['anchor' => 'page', 'width' => '2cm', 'height' => '2cm'])
        );
    }

    public function testRejectsUnknownGeometry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ShapeGeometry::preset('not-a-shape');
    }
}
