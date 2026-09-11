<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Elements;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Elements\DrawTextBox;
use PHPUnit\Framework\TestCase;

final class FrameLayout01CSlice5CompatibilityPolicyTest extends TestCase
{
    public function testCompatibilityPoliciesShareOneSemanticGraphicRequirement(): void
    {
        $box = (new DrawTextBox('PolicyBox', [
            'background-color' => '#eeeeee',
            'wrap-influence' => 'once-concurrent',
        ]))
            ->flowWithText(true)
            ->setAllowOverlap(false)
            ->setFrameLayout([
                'anchor' => 'paragraph',
                'horizontal' => [
                    'alignment' => 'right',
                    'relative-to' => 'paragraph',
                ],
                'wrap' => 'parallel',
            ]);

        $requirements = iterator_to_array($box->getOwnStyleRequirements(), false);

        self::assertCount(1, $requirements);
        self::assertInstanceOf(StyleRequirement::class, $requirements[0]);
        self::assertSame([
            'style:graphic-properties' => [
                'draw:fill' => 'solid',
                'draw:fill-color' => '#eeeeee',
                'draw:wrap-influence-on-position' => 'once-concurrent',
                'fo:background-color' => '#eeeeee',
                'loext:allow-overlap' => 'false',
                'style:flow-with-text' => 'true',
                'style:horizontal-pos' => 'right',
                'style:horizontal-rel' => 'paragraph',
                'style:wrap' => 'parallel',
            ],
        ], $requirements[0]->propertyGroups());
    }

    public function testChangingFriendlyWrapDoesNotClearCompatibilityPolicies(): void
    {
        $box = (new DrawTextBox('WrapOrderBox', [
            'wrap-influence' => 'once-concurrent',
        ]))
            ->flowWithText(true)
            ->setAllowOverlap(true)
            ->setFrameWrap('parallel')
            ->setFrameWrap('none');

        $requirement = iterator_to_array($box->getOwnStyleRequirements(), false)[0];
        $properties = $requirement->propertyGroups()['style:graphic-properties'];

        self::assertSame('none', $properties['style:wrap']);
        self::assertSame('true', $properties['style:flow-with-text']);
        self::assertSame('once-concurrent', $properties['draw:wrap-influence-on-position']);
        self::assertSame('true', $properties['loext:allow-overlap']);
    }

    public function testHistoricalWrapInfluenceValueRemainsUninterpreted(): void
    {
        $box = new DrawTextBox('HistoricalPolicyBox', [
            'wrap-influence' => 'none',
        ]);

        $requirement = iterator_to_array($box->getOwnStyleRequirements(), false)[0];

        self::assertSame(
            'none',
            $requirement
                ->propertyGroups()['style:graphic-properties']['draw:wrap-influence-on-position']
        );
    }

    public function testEquivalentPolicyStateProducesStableSemanticStyleIdentity(): void
    {
        $first = (new DrawTextBox('First', [
            'wrap-influence' => 'once-concurrent',
        ]))
            ->flowWithText(true)
            ->setAllowOverlap(false)
            ->setFrameWrap('parallel');

        $second = (new DrawTextBox('Second', [
            'wrap-influence' => 'once-concurrent',
        ]))
            ->setFrameWrap('parallel')
            ->setAllowOverlap(false)
            ->flowWithText(true);

        $firstRequirement = iterator_to_array($first->getOwnStyleRequirements(), false)[0];
        $secondRequirement = iterator_to_array($second->getOwnStyleRequirements(), false)[0];

        self::assertSame($firstRequirement->name(), $secondRequirement->name());
        self::assertSame($firstRequirement->propertyGroups(), $secondRequirement->propertyGroups());

        $firstDom = new DOMDocument('1.0', 'UTF-8');
        $secondDom = new DOMDocument('1.0', 'UTF-8');

        $firstFrame = $this->frame($first, $firstDom);
        $secondFrame = $this->frame($second, $secondDom);

        self::assertSame(
            $firstFrame->getAttribute('draw:style-name'),
            $secondFrame->getAttribute('draw:style-name')
        );
    }

    private function frame(DrawTextBox $box, DOMDocument $dom): DOMElement
    {
        $node = $box->toDomNode($dom);

        if ($node instanceof DOMElement && $node->nodeName === 'draw:frame') {
            return $node;
        }

        self::assertInstanceOf(DOMElement::class, $node);
        self::assertInstanceOf(DOMElement::class, $node->firstChild);

        return $node->firstChild;
    }
}
