<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\ImageElement;
use PHPUnit\Framework\TestCase;

final class FrameLayout01CSlice3ImageElementTest extends TestCase
{
    public function testFriendlyImageLayoutUsesNativeCarrierOwnership(): void
    {
        $image = (new ImageElement($this->imagePath(), [
            'width' => '2cm',
        ]))->setFrameLayout([
            'anchor' => 'paragraph',
            'width' => '4cm',
            'height' => '2cm',
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

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $image->toDomNode($dom);

        self::assertInstanceOf(DOMElement::class, $frame);
        self::assertSame('paragraph', $frame->getAttribute('text:anchor-type'));
        self::assertSame('4cm', $frame->getAttribute('svg:width'));
        self::assertSame('2cm', $frame->getAttribute('svg:height'));
        self::assertFalse($frame->hasAttribute('style:horizontal-pos'));
        self::assertFalse($frame->hasAttribute('style:horizontal-rel'));
        self::assertFalse($frame->hasAttribute('style:vertical-pos'));
        self::assertFalse($frame->hasAttribute('style:vertical-rel'));
        self::assertFalse($frame->hasAttribute('style:wrap'));

        $requirements = iterator_to_array($image->getOwnStyleRequirements(), false);
        self::assertCount(1, $requirements);
        self::assertInstanceOf(StyleRequirement::class, $requirements[0]);
        self::assertSame([
            'style:graphic-properties' => [
                'style:horizontal-pos' => 'center',
                'style:horizontal-rel' => 'paragraph',
                'style:vertical-pos' => 'top',
                'style:vertical-rel' => 'paragraph',
                'style:wrap' => 'parallel',
            ],
        ], $requirements[0]->propertyGroups());
        self::assertSame($requirements[0]->name(), $frame->getAttribute('draw:style-name'));
    }

    public function testFriendlyOffsetsBecomeFrameCoordinatesAndGraphicModes(): void
    {
        $image = (new ImageElement($this->imagePath(), [
            'width' => '2cm',
            'height' => '1cm',
        ]))->setFrameLayout([
            'anchor' => 'paragraph',
            'horizontal' => [
                'offset' => '1.25cm',
                'relative-to' => 'page-content',
            ],
            'vertical' => [
                'offset' => '-0.4cm',
                'relative-to' => 'paragraph',
            ],
        ]);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $image->toDomNode($dom);

        self::assertSame('1.25cm', $frame->getAttribute('svg:x'));
        self::assertSame('-0.4cm', $frame->getAttribute('svg:y'));

        $requirement = iterator_to_array($image->getOwnStyleRequirements(), false)[0];
        self::assertSame([
            'style:graphic-properties' => [
                'style:horizontal-pos' => 'from-left',
                'style:horizontal-rel' => 'page-content',
                'style:vertical-pos' => 'from-top',
                'style:vertical-rel' => 'paragraph',
            ],
        ], $requirement->propertyGroups());
    }

    public function testFriendlyMaterializationPreservesObservableImageOptionsCompatibility(): void
    {
        $image = (new ImageElement($this->imagePath(), [
            'width' => '2cm',
            'align' => 'right',
        ]))->setFrameLayout([
            'anchor' => 'paragraph',
            'horizontal' => [
                'alignment' => 'left',
                'relative-to' => 'paragraph',
            ],
            'wrap' => 'right',
        ]);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $image->toDomNode($dom);

        $options = $image->getImageOptions();

        self::assertSame('left', $options['style:horizontal-pos']);
        self::assertSame('paragraph', $options['style:horizontal-rel']);
        self::assertSame('right', $options['style:wrap']);
    }

    public function testFriendlyImageLayoutIsStableAcrossRepeatedMaterialization(): void
    {
        $image = (new ImageElement($this->imagePath(), [
            'width' => '2cm',
        ]))->setFrameLayout([
            'anchor' => 'paragraph',
            'horizontal' => [
                'alignment' => 'right',
                'relative-to' => 'paragraph',
            ],
            'wrap' => 'left',
        ]);

        $firstDom = new DOMDocument('1.0', 'UTF-8');
        $first = $image->toDomNode($firstDom);
        $firstOptions = $image->getImageOptions();

        $secondDom = new DOMDocument('1.0', 'UTF-8');
        $second = $image->toDomNode($secondDom);

        self::assertSame($firstOptions, $image->getImageOptions());
        self::assertSame(
            $first->getAttribute('draw:style-name'),
            $second->getAttribute('draw:style-name')
        );
        self::assertSame(
            $first->getAttribute('svg:width'),
            $second->getAttribute('svg:width')
        );
    }

    public function testEmptyMasterLayoutReturnsToLegacyAlignmentBehavior(): void
    {
        $image = (new ImageElement($this->imagePath(), [
            'width' => '2cm',
            'align' => 'right',
        ]))
            ->setFrameHorizontalAlignment('center', 'paragraph')
            ->setFrameLayout([]);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $image->toDomNode($dom);

        self::assertSame('right', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('left', $frame->getAttribute('style:wrap'));
        self::assertSame('paragraph', $frame->getAttribute('style:horizontal-rel'));
    }

    public function testLegacyAlignmentCompatibilityRemainsUntouchedWithoutFriendlyLayout(): void
    {
        $image = new ImageElement($this->imagePath(), [
            'width' => '2cm',
            'align' => 'center',
        ]);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $image->toDomNode($dom);

        self::assertSame('center', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('none', $frame->getAttribute('style:wrap'));
    }

    public function testFriendlyPercentageAlignmentIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ImageElement($this->imagePath(), [
            'width' => '2cm',
        ]))->setFrameLayout([
            'horizontal' => [
                'alignment' => '50%',
                'relative-to' => 'paragraph',
            ],
        ]);
    }

    public function testConstructorAutoscalingRemainsAvailableBeforeFriendlyOverride(): void
    {
        [$pixelWidth, $pixelHeight] = getimagesize($this->imagePath());
        $expectedHeight = round(2.0 * ($pixelHeight / $pixelWidth), 3) . 'cm';

        $image = new ImageElement($this->imagePath(), [
            'width' => '2cm',
        ]);

        self::assertSame($expectedHeight, $image->getImageOptions()['svg:height']);

        $image->setFrameLayout([
            'anchor' => 'paragraph',
            'horizontal' => [
                'alignment' => 'left',
                'relative-to' => 'paragraph',
            ],
        ]);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $image->toDomNode($dom);

        self::assertSame('2cm', $frame->getAttribute('svg:width'));
        self::assertSame($expectedHeight, $frame->getAttribute('svg:height'));
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}
