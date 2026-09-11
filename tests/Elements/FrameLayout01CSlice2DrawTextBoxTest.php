<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\DrawTextBox;
use PHPUnit\Framework\TestCase;

final class FrameLayout01CSlice2DrawTextBoxTest extends TestCase
{
    public function testMasterFrameLayoutUsesNativeCarrierOwnership(): void
    {
        $box = (new DrawTextBox('FriendlyBox', [
            'background-color' => '#eeeeee',
        ]))->setFrameLayout([
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

        $frame = $this->frame($box);

        self::assertSame('paragraph', $frame->getAttribute('text:anchor-type'));
        self::assertSame('6cm', $frame->getAttribute('svg:width'));
        self::assertSame('4cm', $frame->getAttribute('svg:height'));
        self::assertFalse($frame->hasAttribute('style:horizontal-pos'));
        self::assertFalse($frame->hasAttribute('style:horizontal-rel'));
        self::assertFalse($frame->hasAttribute('style:vertical-pos'));
        self::assertFalse($frame->hasAttribute('style:vertical-rel'));

        $requirements = iterator_to_array($box->getOwnStyleRequirements(), false);
        self::assertCount(1, $requirements);
        self::assertInstanceOf(StyleRequirement::class, $requirements[0]);
        self::assertSame([
            'style:graphic-properties' => [
                'draw:fill' => 'solid',
                'draw:fill-color' => '#eeeeee',
                'fo:background-color' => '#eeeeee',
                'style:horizontal-pos' => 'center',
                'style:horizontal-rel' => 'paragraph',
                'style:vertical-pos' => 'top',
                'style:vertical-rel' => 'paragraph',
                'style:wrap' => 'parallel',
            ],
        ], $requirements[0]->propertyGroups());
        self::assertSame(
            $requirements[0]->name(),
            $frame->getAttribute('draw:style-name')
        );
    }

    public function testFriendlyOffsetsProjectToFrameCoordinatesAndGraphicModes(): void
    {
        $box = (new DrawTextBox('OffsetBox'))->setFrameLayout([
            'anchor' => 'paragraph',
            'horizontal' => [
                'offset' => '1.5cm',
                'relative-to' => 'paragraph-content',
            ],
            'vertical' => [
                'offset' => '-0.3cm',
                'relative-to' => 'paragraph',
            ],
        ]);

        $frame = $this->frame($box);
        self::assertSame('1.5cm', $frame->getAttribute('svg:x'));
        self::assertSame('-0.3cm', $frame->getAttribute('svg:y'));

        $requirement = iterator_to_array($box->getOwnStyleRequirements(), false)[0];
        self::assertSame([
            'style:graphic-properties' => [
                'style:horizontal-pos' => 'from-left',
                'style:horizontal-rel' => 'paragraph-content',
                'style:vertical-pos' => 'from-top',
                'style:vertical-rel' => 'paragraph',
            ],
        ], $requirement->propertyGroups());
    }

    public function testConvenienceMethodsShareOneSemanticState(): void
    {
        $box = (new DrawTextBox('ConvenienceBox'))
            ->setFrameAnchor('paragraph')
            ->setFrameHorizontalAlignment('right', 'page-content')
            ->setFrameVerticalAlignment('bottom', 'paragraph')
            ->setFrameWrap('none');

        $requirement = iterator_to_array($box->getOwnStyleRequirements(), false)[0];

        self::assertSame([
            'style:graphic-properties' => [
                'style:horizontal-pos' => 'right',
                'style:horizontal-rel' => 'page-content',
                'style:vertical-pos' => 'bottom',
                'style:vertical-rel' => 'paragraph',
                'style:wrap' => 'none',
            ],
        ], $requirement->propertyGroups());
    }

    public function testLaterOffsetReplacesFriendlyAlignmentOnProducer(): void
    {
        $box = (new DrawTextBox('OrderBox'))
            ->setFrameHorizontalAlignment('center', 'paragraph')
            ->setFrameHorizontalOffset('2cm', 'paragraph');

        $frame = $this->frame($box);
        self::assertSame('2cm', $frame->getAttribute('svg:x'));

        $requirement = iterator_to_array($box->getOwnStyleRequirements(), false)[0];
        self::assertSame('from-left', $requirement->propertyGroups()['style:graphic-properties']['style:horizontal-pos']);
        self::assertArrayNotHasKey(
            'alignment',
            $requirement->propertyGroups()['style:graphic-properties']
        );
    }

    public function testMasterLayoutOverridesLegacyGeometryWithoutChangingLegacyConstructorBehavior(): void
    {
        $box = (new DrawTextBox('OverrideBox', [
            'width' => '3cm',
            'height' => '2cm',
            'anchor' => 'paragraph',
        ]))->setFrameLayout([
            'anchor' => 'page',
            'width' => '7cm',
            'height' => '5cm',
        ]);

        $frame = $this->frame($box);

        self::assertSame('page', $frame->getAttribute('text:anchor-type'));
        self::assertSame('7cm', $frame->getAttribute('svg:width'));
        self::assertSame('5cm', $frame->getAttribute('svg:height'));
    }

    public function testEmptyMasterLayoutReturnsToLegacyCompatibilityBehavior(): void
    {
        $box = (new DrawTextBox('ResetBox', [
            'width' => '3cm',
            'height' => '2cm',
            'horizontal-pos' => '50%',
            'horizontal-rel' => 'page',
        ]))
            ->setFrameHorizontalAlignment('center', 'paragraph')
            ->setFrameLayout([]);

        $frame = $this->frame($box);

        self::assertSame('3cm', $frame->getAttribute('svg:width'));
        self::assertSame('50%', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('page', $frame->getAttribute('style:horizontal-rel'));
    }

    public function testFriendlyPercentageAlignmentIsRejectedOnDrawTextBox(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DrawTextBox('InvalidBox'))->setFrameLayout([
            'horizontal' => [
                'alignment' => '50%',
                'relative-to' => 'paragraph',
            ],
        ]);
    }

    public function testLegacyPercentageSetterRemainsPassThrough(): void
    {
        $box = (new DrawTextBox('LegacyBox'))
            ->setHorizontalPosition('50%', 'page');

        $frame = $this->frame($box);

        self::assertSame('50%', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('page', $frame->getAttribute('style:horizontal-rel'));
    }

    private function frame(DrawTextBox $box): DOMElement
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $node = $box->toDomNode($dom);

        if ($node instanceof DOMElement && $node->nodeName === 'draw:frame') {
            return $node;
        }

        self::assertInstanceOf(DOMElement::class, $node);
        self::assertInstanceOf(DOMElement::class, $node->firstChild);

        return $node->firstChild;
    }
}
