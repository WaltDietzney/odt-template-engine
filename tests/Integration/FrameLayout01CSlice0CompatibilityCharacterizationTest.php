<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\ImageElement;
use PHPUnit\Framework\TestCase;

final class FrameLayout01CSlice0CompatibilityCharacterizationTest extends TestCase
{
    /** @var list<DOMDocument> */
    private array $domDocuments = [];

    public function testLegacyCoordinateModeSettersDoNotInventCoordinates(): void
    {
        $box = (new DrawTextBox('LegacyCoordinateModes'))
            ->setHorizontalPosition('from-left', 'page-content')
            ->setVerticalPosition('from-top', 'page-content');

        $frame = $this->frame($box);

        self::assertSame('from-left', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('page-content', $frame->getAttribute('style:horizontal-rel'));
        self::assertSame('from-top', $frame->getAttribute('style:vertical-pos'));
        self::assertSame('page-content', $frame->getAttribute('style:vertical-rel'));
        self::assertFalse($frame->hasAttribute('svg:x'));
        self::assertFalse($frame->hasAttribute('svg:y'));
    }

    /**
     * @dataProvider imageAlignmentProvider
     */
    public function testImageElementAlignmentCompatibilityResolvesIntoCurrentFrameAttributes(
        string $align,
        string $expectedWrap,
        string $expectedPosition,
        string $expectedRelation
    ): void {
        $image = new ImageElement($this->imagePath(), [
            'width' => '2cm',
            'height' => '1cm',
            'align' => $align,
        ]);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $image->toDomNode($dom);

        self::assertInstanceOf(DOMElement::class, $frame);
        self::assertSame($expectedWrap, $frame->getAttribute('style:wrap'));
        self::assertSame($expectedPosition, $frame->getAttribute('style:horizontal-pos'));
        self::assertSame($expectedRelation, $frame->getAttribute('style:horizontal-rel'));

        $options = $image->getImageOptions();
        self::assertSame($expectedWrap, $options['style:wrap']);
        self::assertSame($expectedPosition, $options['style:horizontal-pos']);
        self::assertSame($expectedRelation, $options['style:horizontal-rel']);
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function imageAlignmentProvider(): iterable
    {
        yield 'left' => ['left', 'right', 'left', 'paragraph'];
        yield 'right' => ['right', 'left', 'right', 'paragraph'];
        yield 'center' => ['center', 'none', 'center', 'paragraph'];
        yield 'absolute' => ['absolute', 'none', 'from-left', 'page-content'];
    }

    public function testAbsoluteImageAlignmentDoesNotInventHorizontalCoordinate(): void
    {
        $image = new ImageElement($this->imagePath(), [
            'width' => '2cm',
            'height' => '1cm',
            'align' => 'absolute',
        ]);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $image->toDomNode($dom);

        self::assertSame('from-left', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('page-content', $frame->getAttribute('style:horizontal-rel'));
        self::assertFalse($frame->hasAttribute('svg:x'));
    }

    public function testExplicitRawImageCoordinatesAreCurrentlyDroppedByStyleMapper(): void
    {
        $image = new ImageElement($this->imagePath(), [
            'width' => '2cm',
            'height' => '1cm',
            'horizontal-pos' => 'from-left',
            'horizontal-rel' => 'page-content',
            'vertical-pos' => 'from-top',
            'vertical-rel' => 'page-content',
            'svg:x' => '1.25cm',
            'svg:y' => '2.5cm',
        ]);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $frame = $image->toDomNode($dom);

        self::assertSame('from-left', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('page-content', $frame->getAttribute('style:horizontal-rel'));
        self::assertSame('from-top', $frame->getAttribute('style:vertical-pos'));
        self::assertSame('page-content', $frame->getAttribute('style:vertical-rel'));
        self::assertFalse($frame->hasAttribute('svg:x'));
        self::assertFalse($frame->hasAttribute('svg:y'));

        $options = $image->getImageOptions();
        self::assertArrayNotHasKey('svg:x', $options);
        self::assertArrayNotHasKey('svg:y', $options);
    }

    public function testImageWidthOnlyConstructorCalculatesHeightFromImageRatio(): void
    {
        [$pixelWidth, $pixelHeight] = getimagesize($this->imagePath());
        $expectedHeight = round(2.0 * ($pixelHeight / $pixelWidth), 3) . 'cm';

        $image = new ImageElement($this->imagePath(), [
            'width' => '2cm',
        ]);

        $options = $image->getImageOptions();

        self::assertSame('2cm', $options['svg:width']);
        self::assertSame($expectedHeight, $options['svg:height']);
    }

    public function testImageHeightOnlyConstructorCalculatesWidthFromImageRatio(): void
    {
        [$pixelWidth, $pixelHeight] = getimagesize($this->imagePath());
        $expectedWidth = round(2.0 * ($pixelWidth / $pixelHeight), 3) . 'cm';

        $image = new ImageElement($this->imagePath(), [
            'height' => '2cm',
        ]);

        $options = $image->getImageOptions();

        self::assertSame($expectedWidth, $options['svg:width']);
        self::assertSame('2cm', $options['svg:height']);
    }

    public function testImageMaterializationMutationIsStableAcrossRepeatedCalls(): void
    {
        $image = new ImageElement($this->imagePath(), [
            'width' => '2cm',
            'align' => 'right',
        ]);

        $before = $image->getImageOptions();
        self::assertArrayNotHasKey('style:wrap', $before);

        $firstDom = new DOMDocument('1.0', 'UTF-8');
        $first = $image->toDomNode($firstDom);
        $afterFirst = $image->getImageOptions();

        $secondDom = new DOMDocument('1.0', 'UTF-8');
        $second = $image->toDomNode($secondDom);
        $afterSecond = $image->getImageOptions();

        self::assertSame($afterFirst, $afterSecond);
        self::assertSame(
            $first->attributes?->getNamedItem('style:wrap')?->nodeValue,
            $second->attributes?->getNamedItem('style:wrap')?->nodeValue
        );
        self::assertSame(
            $first->attributes?->getNamedItem('style:horizontal-pos')?->nodeValue,
            $second->attributes?->getNamedItem('style:horizontal-pos')?->nodeValue
        );
    }

    private function frame(DrawTextBox $box): DOMElement
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $this->domDocuments[] = $dom;
        $node = $box->toDomNode($dom);

        if ($node instanceof DOMElement && $node->nodeName === 'draw:frame') {
            return $node;
        }

        self::assertInstanceOf(DOMElement::class, $node);
        self::assertInstanceOf(DOMElement::class, $node->firstChild);

        return $node->firstChild;
    }

    private function imagePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/WaltDietzney.png';
    }
}
