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
        self::assertSame('rectangle', $node->lastChild?->attributes?->getNamedItem('draw:type')?->nodeValue);
        self::assertSame('text:p', $node->firstChild?->nodeName);
        self::assertSame('draw:enhanced-geometry', $node->lastChild?->nodeName);
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
            self::assertSame($type === 'arrow-contour' ? 'right-arrow' : $type, $node->lastChild?->attributes?->getNamedItem('draw:type')?->nodeValue);
            self::assertNotEmpty($node->lastChild?->attributes?->getNamedItem('draw:enhanced-path')?->nodeValue);
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

    public function testHexagonMatchesLibreOfficeReferenceGeometry(): void
    {
        $geometry = ShapeGeometry::preset('hexagon');
        self::assertSame('hexagon', $geometry->type());
        self::assertSame('0 0 21600 21600', $geometry->viewBox());
        self::assertSame(
            'M 5400 0 L 16200 0 21600 10800 16200 21600 5400 21600 0 10800 Z N',
            $geometry->path()
        );

        $shape = new ShapeElement(
            $geometry,
            ShapeAppearance::solid('#2365B5'),
            DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '4cm', 'height' => '4cm'])
        );
        $node = $shape->toDomNode(new DOMDocument());
        self::assertSame('hexagon', $node->lastChild?->attributes?->getNamedItem('draw:type')?->nodeValue);
        self::assertSame($geometry->path(), $node->lastChild?->attributes?->getNamedItem('draw:enhanced-path')?->nodeValue);
    }

    public function testWriterAuthoredAdjustableShapesPreserveGeometry(): void
    {
        foreach (['isosceles-triangle' => [8, 1], 'ring' => [2, 1]] as $type => [$equations, $handles]) {
            $shape = new ShapeElement(
                ShapeGeometry::preset($type),
                ShapeAppearance::solid('#2365B5'),
                DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '4cm', 'height' => '4cm'])
            );
            $node = $shape->toDomNode(new DOMDocument());
            $geometry = $node->lastChild;
            self::assertSame($type, $geometry?->attributes?->getNamedItem('draw:type')?->nodeValue);
            self::assertNotNull($geometry?->attributes?->getNamedItem('draw:modifiers'));
            $xml = $node->ownerDocument?->saveXML($node);
            self::assertIsString($xml);
            self::assertSame($equations, substr_count($xml, '<draw:equation'));
            self::assertSame($handles, substr_count($xml, '<draw:handle'));
        }
    }

    public function testWriterAuthoredFlowchartPresets(): void
    {
        foreach (['flowchart-stored-data', 'flowchart-off-page-connector', 'flowchart-punched-tape'] as $type) {
            $shape = new ShapeElement(
                ShapeGeometry::preset($type),
                ShapeAppearance::solid('#2365B5'),
                DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '4cm', 'height' => '4cm'])
            );
            $dom = new DOMDocument();
            $node = $shape->toDomNode($dom);
            $geometry = $node->lastChild;
            self::assertSame($type, $geometry?->attributes?->getNamedItem('draw:type')?->nodeValue);
            self::assertNotEmpty($geometry?->attributes?->getNamedItem('draw:enhanced-path')?->nodeValue);
        }
    }

    public function testCharacterizedCustomContourCandidates(): void
    {
        foreach (['pentagon', 'chevron', 'star5'] as $type) {
            $shape = new ShapeElement(
                ShapeGeometry::preset($type),
                ShapeAppearance::solid('#2365B5'),
                DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '4cm', 'height' => '4cm'])
            );
            $dom = new DOMDocument();
            $node = $shape->toDomNode($dom);
            $geometry = $node->lastChild;
            self::assertSame($type, $geometry?->attributes?->getNamedItem('draw:type')?->nodeValue);
            self::assertNotEmpty($geometry?->attributes?->getNamedItem('draw:enhanced-path')?->nodeValue);
        }
    }

    public function testLibreOfficeRoundRectangleReferenceGeometry(): void
    {
        $geometry = ShapeGeometry::preset('round-rectangle');
        self::assertSame('round-rectangle', $geometry->type());
        self::assertNotEmpty($geometry->equations());
        self::assertNotEmpty($geometry->handles());
        $shape = new ShapeElement(
            $geometry,
            ShapeAppearance::solid('#2365B5'),
            DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '4cm', 'height' => '4cm'])
        );
        $node = $shape->toDomNode(new DOMDocument());
        self::assertSame('round-rectangle', $node->lastChild?->attributes?->getNamedItem('draw:type')?->nodeValue);
    }

    public function testRemainingSixCatalogShapesProduceNativeGeometry(): void
    {
        foreach (['triangle', 'cross', 'speech-bubble', 'left-arrow', 'up-arrow', 'down-arrow'] as $type) {
            $geometry = ShapeGeometry::preset($type);
            $shape = new ShapeElement(
                $geometry,
                ShapeAppearance::solid('#2365B5'),
                DrawingLayout::fromArray(['anchor' => 'paragraph', 'width' => '4cm', 'height' => '4cm'])
            );
            $node = $shape->toDomNode(new DOMDocument());
            self::assertSame($type, $node->lastChild?->attributes?->getNamedItem('draw:type')?->nodeValue);
            self::assertSame($geometry->path(), $node->lastChild?->attributes?->getNamedItem('draw:enhanced-path')?->nodeValue);
        }
        self::assertNotEmpty(ShapeGeometry::preset('left-arrow')->equations());
        self::assertNotEmpty(ShapeGeometry::preset('left-arrow')->handles());
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
