<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Elements\DrawTextBox;
use OdtTemplateEngine\Elements\Paragraph;
use PHPUnit\Framework\TestCase;

final class FrameLayout01A0CurrentApiCharacterizationTest extends TestCase
{
    /** @var list<DOMDocument> */
    private array $domDocuments = [];

    public function testHorizontalSetterFamiliesShareImplementationButHaveDifferentDefaultRelations(): void
    {
        $short = (new DrawTextBox('Short'))->setHorizontalPos('right');
        $long = (new DrawTextBox('Long'))->setHorizontalPosition('right');

        $shortFrame = $this->frame($short);
        $longFrame = $this->frame($long);

        self::assertSame('right', $shortFrame->getAttribute('style:horizontal-pos'));
        self::assertSame('char', $shortFrame->getAttribute('style:horizontal-rel'));

        self::assertSame('right', $longFrame->getAttribute('style:horizontal-pos'));
        self::assertSame('page', $longFrame->getAttribute('style:horizontal-rel'));
    }

    public function testVerticalSetterFamiliesShareImplementationButHaveDifferentDefaultRelations(): void
    {
        $short = (new DrawTextBox('Short'))->setVerticalPos('top');
        $long = (new DrawTextBox('Long'))->setVerticalPosition('top');

        $shortFrame = $this->frame($short);
        $longFrame = $this->frame($long);

        self::assertSame('top', $shortFrame->getAttribute('style:vertical-pos'));
        self::assertSame('baseline', $shortFrame->getAttribute('style:vertical-rel'));

        self::assertSame('top', $longFrame->getAttribute('style:vertical-pos'));
        self::assertSame('page', $longFrame->getAttribute('style:vertical-rel'));
    }

    public function testFloatingTextBoxIsWrappedInParagraphButAsCharFrameIsReturnedDirectly(): void
    {
        $floating = new DrawTextBox('Floating', ['anchor' => 'paragraph']);
        $inline = new DrawTextBox('Inline', ['anchor' => 'as-char']);

        $dom = new DOMDocument('1.0', 'UTF-8');

        $floatingNode = $floating->toDomNode($dom);
        self::assertSame('text:p', $floatingNode->nodeName);
        self::assertSame('draw:frame', $floatingNode->firstChild?->nodeName);

        $inlineNode = $inline->toDomNode($dom);
        self::assertSame('draw:frame', $inlineNode->nodeName);
        self::assertSame('as-char', $inlineNode->attributes?->getNamedItem('text:anchor-type')?->nodeValue);
    }

    public function testSample17RightBoxPercentPositionIsPassedThroughVerbatim(): void
    {
        $box = (new DrawTextBox('Box1', [
            'width' => '6cm',
            'height' => '4cm',
            'horizontal-pos' => '100%',
            'horizontal-rel' => 'page',
            'wrap-influence' => 'none',
            'background-color' => '#e0f7fa',
            'border' => '0.04cm solid #00796b',
            'padding' => '0.2cm',
        ]))
            ->addElement((new Paragraph())->addText('Right'));

        $frame = $this->frame($box);

        self::assertSame('100%', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('page', $frame->getAttribute('style:horizontal-rel'));
        self::assertSame('6cm', $frame->getAttribute('svg:width'));
        self::assertSame('4cm', $frame->getAttribute('svg:height'));

        $legacyProperties = $this->legacyProperties($box);
        self::assertSame('100%', $legacyProperties['style:horizontal-pos']);
        self::assertSame('page', $legacyProperties['style:horizontal-rel']);
        self::assertSame('none', $legacyProperties['draw:wrap-influence-on-position']);
    }

    public function testSample17CenterBoxPercentPositionsArePassedThroughVerbatim(): void
    {
        $box = (new DrawTextBox('Box2', [
            'width' => '5cm',
            'height' => '6cm',
            'horizontal-pos' => '50%',
            'horizontal-rel' => 'page',
            'vertical-pos' => '50%',
            'vertical-rel' => 'page',
            'wrap-influence' => 'once-concurrent',
            'background-color' => '#fff3e0',
            'border' => '0.02cm dashed #e65100',
            'padding' => '0.3cm',
            'rx' => '0.5cm',
            'ry' => '0.5cm',
        ]))
            ->addElement((new Paragraph())->addText('Center'));

        $frame = $this->frame($box);

        self::assertSame('50%', $frame->getAttribute('style:horizontal-pos'));
        self::assertSame('page', $frame->getAttribute('style:horizontal-rel'));
        self::assertSame('50%', $frame->getAttribute('style:vertical-pos'));
        self::assertSame('page', $frame->getAttribute('style:vertical-rel'));

        $legacyProperties = $this->legacyProperties($box);
        self::assertSame('50%', $legacyProperties['style:horizontal-pos']);
        self::assertSame('page', $legacyProperties['style:horizontal-rel']);
        self::assertSame('50%', $legacyProperties['style:vertical-pos']);
        self::assertSame('page', $legacyProperties['style:vertical-rel']);
        self::assertSame('once-concurrent', $legacyProperties['draw:wrap-influence-on-position']);
        self::assertSame('0.5cm', $legacyProperties['svg:rx']);
        self::assertSame('0.5cm', $legacyProperties['svg:ry']);
    }

    public function testFlowWithTextAndAllowOverlapRemainLegacyCompatibleAndNowHaveSemanticCarrier(): void
    {
        $box = (new DrawTextBox('LegacyLayout'))
            ->flowWithText()
            ->setAllowOverlap();

        $legacyProperties = $this->legacyProperties($box);

        self::assertSame('true', $legacyProperties['style:flow-with-text']);
        self::assertSame('true', $legacyProperties['loext:allow-overlap']);

        $semanticRequirements = iterator_to_array($box->getOwnStyleRequirements(), false);
        self::assertCount(1, $semanticRequirements);
        self::assertSame([
            'style:graphic-properties' => [
                'loext:allow-overlap' => 'true',
                'style:flow-with-text' => 'true',
            ],
        ], $semanticRequirements[0]->propertyGroups());
    }

    private function frame(DrawTextBox $box): DOMElement
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $this->domDocuments[] = $dom;
        $node = $box->toDomNode($dom);
        $dom->appendChild($node);

        if ($node instanceof DOMElement && $node->nodeName === 'draw:frame') {
            return $node;
        }

        self::assertInstanceOf(DOMElement::class, $node);

        $frames = $dom->getElementsByTagName('draw:frame');
        self::assertSame(1, $frames->length);

        $frame = $frames->item(0);
        self::assertInstanceOf(DOMElement::class, $frame);

        return $frame;
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyProperties(DrawTextBox $box): array
    {
        $requirements = $box->getOwnFrameStyleRequirements();
        self::assertCount(1, $requirements);

        $properties = reset($requirements);
        self::assertIsArray($properties);

        return $properties;
    }
}
