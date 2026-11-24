<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use DOMDocument;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StructuredInsertionMode;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\ShapeAppearance;
use OdtTemplateEngine\Elements\ShapeElement;
use OdtTemplateEngine\Elements\ShapeGeometry;
use OdtTemplateEngine\Elements\ShapeTextLayout;
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

    public function testOptionalParagraphIsEmittedBeforeGeometryAndOwnedForStyles(): void
    {
        $shape = new ShapeElement(
            ShapeGeometry::preset('rectangle'),
            ShapeAppearance::none(),
            DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '3cm', 'height' => '1cm'])
        );
        $paragraph = (new Paragraph())->addText('Teamarbeit', [
            'color' => '#ffffff',
            'font-size' => '12pt',
            'font-family' => 'Liberation Sans',
        ]);
        $shape->addParagraph($paragraph);
        $node = $shape->toDomNode(new DOMDocument());
        self::assertSame('text:p', $node->firstChild?->nodeName);
        self::assertSame('Teamarbeit', $node->firstChild?->textContent);
        self::assertSame('Standard', $node->attributes->getNamedItem('draw:text-style-name')?->nodeValue);
        self::assertSame('draw:enhanced-geometry', $node->lastChild?->nodeName);
        self::assertSame([$paragraph], iterator_to_array($shape->ownedElements()));
        self::assertNotEmpty(iterator_to_array($paragraph->getOwnStyleRequirements()));
    }

    public function testTextLayoutProjectsVerticalAlignmentAndPadding(): void
    {
        $shape = new ShapeElement(
            ShapeGeometry::preset('rectangle'),
            ShapeAppearance::none(),
            DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '3cm', 'height' => '1cm'])
        );
        $shape->setTextLayout(new ShapeTextLayout('middle', ['left' => '0.2cm', 'right' => '0.2cm'], 'left'));
        $style = iterator_to_array($shape->getOwnStyleRequirements())[0];
        $props = $style->propertyGroups()['style:graphic-properties'];
        self::assertSame('middle', $props['draw:textarea-vertical-align']);
        self::assertSame('left', $props['draw:textarea-horizontal-align']);
        self::assertSame('0.2cm', $props['fo:padding-left']);
        self::assertSame('0.2cm', $props['fo:padding-right']);
    }

    public function testRunThroughShapeIsPlacedInForeground(): void
    {
        $shape = new ShapeElement(
            ShapeGeometry::preset('rectangle'),
            ShapeAppearance::solid('#3185C5'),
            DrawingLayout::fromArray([
                'anchor' => 'paragraph',
                'width' => '3cm',
                'height' => '0.25cm',
                'wrap' => 'run-through',
            ])
        );
        $props = iterator_to_array($shape->getOwnStyleRequirements())[0]
            ->propertyGroups()['style:graphic-properties'];
        self::assertSame('run-through', $props['style:wrap']);
        self::assertSame('foreground', $props['style:run-through']);
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
