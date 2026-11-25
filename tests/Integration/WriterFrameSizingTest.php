<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Integration;

use DOMDocument;
use OdtTemplateEngine\Elements\DrawTextBox;
use PHPUnit\Framework\TestCase;

final class WriterFrameSizingTest extends TestCase
{
    public function testLegacyFixedAndRawOptionsKeepTheirCarriers(): void
    {
        $box = (new DrawTextBox('legacy', ['width' => '3cm', 'height' => '1cm',
            'draw:auto-grow-height' => 'true', 'fo:margin-left' => '0.08cm']))->setFrameAnchor('as-char');
        $frame = $box->toDomNode(new DOMDocument());
        self::assertSame('3cm', $frame->getAttribute('svg:width'));
        self::assertSame('1cm', $frame->getAttribute('svg:height'));
        self::assertFalse($frame->firstChild->hasAttribute('fo:min-height'));
        $requirements = $box->getOwnFrameStyleRequirements();
        $properties = reset($requirements);
        self::assertSame('true', $properties['draw:auto-grow-height']);
        self::assertSame('0.08cm', $properties['fo:margin-left']);
    }

    public function testDefaultBlockFrameStillUsesParagraphWrapper(): void
    {
        $node = (new DrawTextBox('default'))->toDomNode(new DOMDocument());
        self::assertSame('text:p', $node->nodeName);
        self::assertSame('paragraph', $node->firstChild->getAttribute('text:anchor-type'));
        self::assertFalse($node->firstChild->firstChild->hasAttributes());
    }
    public function testNativeBoundsAndGrowFlagsUseDifferentCarriersAndSpacingUsesSemanticStyles(): void
    {
        $box = (new DrawTextBox('flow'))->setFrameLayout(['anchor' => 'as-char', 'width' => '4cm', 'height' => '0.1cm']);
        self::assertSame($box, $box->setTextBoxSizing([
            'min-height' => '0.1cm', 'min-width' => '1cm',
            'auto-grow-height' => true, 'auto-grow-width' => true,
        ]));
        $box->setFrameSpacing(['padding-left' => '0.152cm', 'padding-right' => '0.2cm', 'margin-left' => '0.08cm']);
        $frame = $box->toDomNode(new DOMDocument());
        self::assertSame('4cm', $frame->getAttribute('svg:width'));
        self::assertSame('0.1cm', $frame->getAttribute('svg:height'));
        $inner = $frame->firstChild;
        self::assertSame('0.1cm', $inner->getAttribute('fo:min-height'));
        self::assertFalse($inner->hasAttribute('fo:max-height'));
        self::assertSame('1cm', $inner->getAttribute('fo:min-width'));
        self::assertFalse($inner->hasAttribute('fo:max-width'));
        self::assertFalse($inner->hasAttribute('draw:auto-grow-height'));
        $requirements = iterator_to_array((function () use ($box) { yield from $box->getOwnStyleRequirements(); })());
        $groups = $requirements[0]->propertyGroups();
        self::assertSame('true', $groups['style:graphic-properties']['draw:auto-grow-height']);
        self::assertSame('true', $groups['style:graphic-properties']['draw:auto-grow-width']);
        self::assertSame('0.152cm', $groups['style:graphic-properties']['fo:padding-left']);
        self::assertSame('0.08cm', $groups['style:graphic-properties']['fo:margin-left']);
        self::assertSame($requirements[0]->name(), $frame->getAttribute('draw:style-name'));
    }

    public function testInvalidBoundsAndSpacingDoNotPartiallyChangeTheFrame(): void
    {
        $box = (new DrawTextBox('valid'))->setTextBoxSizing(['min-height' => '1cm'])
            ->setFrameSpacing(['padding' => '0cm']);
        $dom = new DOMDocument();
        $before = $dom->saveXML($box->toDomNode($dom));
        foreach ([['unknown' => '1cm'], ['min-height' => '0cm'], ['min-width' => '-1cm'],
            ['max-width' => '100%'], ['min-height' => 1], ['auto-grow-height' => 'true'],
            ['min-height' => '1cm', 'auto-grow-height' => false], ['max-width' => '4cm']] as $invalid) {
            try {
                $box->setTextBoxSizing($invalid);
                self::fail('Expected invalid sizing');
            } catch (\InvalidArgumentException) {
                self::assertSame($before, $dom->saveXML($box->toDomNode($dom)));
            }
        }
        foreach ([['margin' => '-1cm'], ['padding-top' => '1%'], ['border' => '1cm'], ['padding' => true]] as $invalid) {
            try {
                $box->setFrameSpacing($invalid);
                self::fail('Expected invalid spacing');
            } catch (\InvalidArgumentException) {
                self::assertSame($before, $dom->saveXML($box->toDomNode($dom)));
            }
        }
        $box->setTextBoxSizing(['min-height' => '25.4mm']);
        $node = $box->toDomNode($dom);
        self::assertSame('25.4mm', $node->firstChild->firstChild->getAttribute('fo:min-height'));
    }

    public function testClearingOverlaysRestoresLegacyOptionsAndStyleIdentity(): void
    {
        $box = new DrawTextBox('restore', ['width' => '3cm', 'height' => '1cm', 'fo:padding-left' => '0.3cm']);
        $dom = new DOMDocument();
        $before = $dom->saveXML($box->toDomNode($dom));
        $box->setTextBoxSizing(['min-height' => '0.2cm', 'auto-grow-height' => true]);
        $box->setFrameSpacing(['padding-left' => '0.1cm', 'margin' => '0cm']);
        $box->setFrameLayout(['anchor' => 'as-char', 'width' => '4cm']);
        $node = $box->toDomNode($dom);
        self::assertTrue($node->firstChild->hasAttribute('fo:min-height'));
        $box->setTextBoxSizing([])->setFrameSpacing([])->setFrameLayout([]);
        self::assertSame($before, $dom->saveXML($box->toDomNode($dom)));
    }

    public function testShorthandUsesExplicitSidesAndOverridesConstructorSpacingWithoutOrderDependence(): void
    {
        $box = new DrawTextBox('sides', ['padding-left' => '0.5cm']);
        $box->setFrameSpacing(['padding-left' => '0.2cm', 'padding' => '0.1cm', 'margin' => '0cm']);
        $styles = $box->getOwnFrameStyleRequirements();
        $properties = reset($styles);
        self::assertSame('0.2cm', $properties['fo:padding-left']);
        self::assertSame('0.1cm', $properties['fo:padding-right']);
        self::assertSame('0cm', $properties['fo:margin-bottom']);
        $box->setFrameSpacing(['padding' => '0.1cm']);
        $styles = $box->getOwnFrameStyleRequirements();
        self::assertSame('0.1cm', reset($styles)['fo:padding-left']);
    }

}
