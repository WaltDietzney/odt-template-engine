<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use InvalidArgumentException;
use OdtTemplateEngine\Elements\DrawingLayout;
use OdtTemplateEngine\Elements\DrawingLayoutProjector;
use PHPUnit\Framework\TestCase;

final class FrameLayout01CSlice1DrawingLayoutTest extends TestCase
{
    public function testAlignmentLayoutProjectsIntoSeparateObjectAndGraphicCarriers(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'width' => '6cm',
            'height' => '4cm',
            'horizontal' => [
                'alignment' => 'center',
                'relative-to' => 'paragraph',
            ],
            'vertical' => [
                'alignment' => 'top',
                'relative-to' => 'paragraph',
            ],
            'wrap' => 'parallel',
        ]);

        $projector = new DrawingLayoutProjector();

        self::assertSame([
            'text:anchor-type' => 'paragraph',
            'svg:width' => '6cm',
            'svg:height' => '4cm',
        ], $projector->objectAttributes($layout));

        self::assertSame([
            'style:horizontal-pos' => 'center',
            'style:horizontal-rel' => 'paragraph',
            'style:vertical-pos' => 'top',
            'style:vertical-rel' => 'paragraph',
            'style:wrap' => 'parallel',
        ], $projector->graphicLayoutProperties($layout));
    }

    public function testOffsetLayoutActivatesFromLeftAndFromTopWithCoordinates(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'horizontal' => [
                'offset' => '1.5cm',
                'relative-to' => 'paragraph-content',
            ],
            'vertical' => [
                'offset' => '0.3cm',
                'relative-to' => 'paragraph',
            ],
        ]);

        $projector = new DrawingLayoutProjector();

        self::assertSame([
            'text:anchor-type' => 'paragraph',
            'svg:x' => '1.5cm',
            'svg:y' => '0.3cm',
        ], $projector->objectAttributes($layout));

        self::assertSame([
            'style:horizontal-pos' => 'from-left',
            'style:horizontal-rel' => 'paragraph-content',
            'style:vertical-pos' => 'from-top',
            'style:vertical-rel' => 'paragraph',
        ], $projector->graphicLayoutProperties($layout));
    }

    public function testLaterOffsetReplacesAlignmentOnSameAxis(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'horizontal' => [
                'alignment' => 'center',
                'relative-to' => 'paragraph',
            ],
        ])->withHorizontalOffset('2cm', 'page-content');

        self::assertSame('offset', $layout->horizontalMode());
        self::assertNull($layout->horizontalAlignment());
        self::assertSame('2cm', $layout->horizontalOffset());
        self::assertSame('page-content', $layout->horizontalRelation());
    }

    public function testLaterAlignmentClearsAuthoredOffsetOnSameAxis(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'horizontal' => [
                'offset' => '2cm',
                'relative-to' => 'paragraph',
            ],
        ])->withHorizontalAlignment('right', 'paragraph');

        self::assertSame('alignment', $layout->horizontalMode());
        self::assertSame('right', $layout->horizontalAlignment());
        self::assertNull($layout->horizontalOffset());
    }

    public function testAsCharDefaultsVerticalAlignmentToBaselineAndRequiresInlineFlow(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'as-char',
        ])->withVerticalAlignment('top');

        self::assertSame('baseline', $layout->verticalRelation());
        self::assertTrue((new DrawingLayoutProjector())->requiresInlineTextFlow($layout));
    }

    public function testAsCharRejectsHorizontalFriendlyPlacement(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DrawingLayout::fromArray([
            'anchor' => 'as-char',
            'horizontal' => [
                'alignment' => 'center',
                'relative-to' => 'paragraph',
            ],
        ]);
    }

    public function testFriendlyAlignmentRejectsPercentagePseudoPosition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'horizontal' => [
                'alignment' => '50%',
                'relative-to' => 'paragraph',
            ],
        ]);
    }

    public function testFriendlyAxisRejectsAlignmentAndOffsetTogether(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'horizontal' => [
                'alignment' => 'center',
                'offset' => '1cm',
                'relative-to' => 'paragraph',
            ],
        ]);
    }

    public function testFriendlyLengthRejectsPercentages(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DrawingLayout::fromArray([
            'width' => '50%',
        ]);
    }

    public function testUnknownFriendlyKeyIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'style:horizontal-pos' => 'center',
        ]);
    }

    public function testPageAnchorDefaultsConvenienceRelationToPage(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'page',
        ])->withHorizontalAlignment('center');

        self::assertSame('page', $layout->horizontalRelation());
    }

    public function testFriendlySizeRejectsNegativeLength(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DrawingLayout::fromArray([
            'width' => '-2cm',
        ]);
    }

    public function testFriendlyOffsetAllowsSignedLength(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'vertical' => [
                'offset' => '-0.3cm',
                'relative-to' => 'paragraph',
            ],
        ]);

        self::assertSame('-0.3cm', $layout->verticalOffset());
    }

    public function testChangingAnchorRejectsIncompatibleRetainedAxisState(): void
    {
        $layout = DrawingLayout::fromArray([
            'anchor' => 'paragraph',
            'horizontal' => [
                'alignment' => 'center',
                'relative-to' => 'paragraph',
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $layout->withAnchor('page');
    }

}
