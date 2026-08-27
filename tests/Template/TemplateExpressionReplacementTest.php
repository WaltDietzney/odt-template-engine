<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Template;

use DOMDocument;
use OdtTemplateEngine\Template\TemplateProcessor;
use PHPUnit\Framework\TestCase;

final class TemplateExpressionReplacementTest extends TestCase
{
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testStyledAndEmbeddedReplacementPreservesContainerAndSiblings(): void
    {
        $dom = $this->document('<text:p><text:span text:style-name="T1" data-test="keep">Role: {{position}} | Company</text:span></text:p>');
        $span = $dom->documentElement->firstChild->firstChild;

        (new TemplateProcessor())->replaceScalarTextInSubtree($dom, ['position' => 'Senior Projektmanager'], static fn (string $filter, mixed $value, ?string $option): string => (string) $value);

        self::assertSame('Role: Senior Projektmanager | Company', $span->textContent);
        self::assertSame('T1', $span->getAttribute('text:style-name'));
        self::assertSame('keep', $span->getAttribute('data-test'));
        self::assertSame('text:p', $span->parentNode->nodeName);
    }

    public function testDifferentStyleFragmentsReceiveValueOnlyInFirstFragment(): void
    {
        $dom = $this->document('<text:p><text:span text:style-name="T29">{{ac</text:span><text:span text:style-name="T30">tiv</text:span><text:span text:style-name="T29">ity}}</text:span></text:p>');
        $paragraph = $dom->documentElement->firstChild;
        $before = $dom->C14N();

        (new TemplateProcessor())->replaceScalarTextInSubtree($dom, ['activity' => 'Leitung'], static fn (string $filter, mixed $value, ?string $option): string => (string) $value);

        self::assertSame('Leitung', $paragraph->childNodes->item(0)->textContent);
        self::assertSame('', $paragraph->childNodes->item(1)->textContent);
        self::assertSame('', $paragraph->childNodes->item(2)->textContent);
        self::assertSame('T29', $paragraph->childNodes->item(0)->getAttribute('text:style-name'));
        self::assertStringContainsString('text:style-name="T30"', $paragraph->C14N());
        self::assertNotSame($before, $dom->C14N());
    }

    public function testBookmarkMarkersRemainInPlaceDuringFragmentReplacement(): void
    {
        $dom = $this->document('<text:p><text:span text:style-name="T29">{{ac</text:span><text:bookmark-start text:name="Activity"/><text:span text:style-name="T29">tivity}}</text:span><text:bookmark-end text:name="Activity"/></text:p>');
        $paragraph = $dom->documentElement->firstChild;

        (new TemplateProcessor())->replaceScalarTextInSubtree($dom, ['activity' => 'Leitung'], static fn (string $filter, mixed $value, ?string $option): string => (string) $value);

        self::assertSame('Leitung', $paragraph->childNodes->item(0)->textContent);
        self::assertSame('text:bookmark-start', $paragraph->childNodes->item(1)->nodeName);
        self::assertSame('text:bookmark-end', $paragraph->childNodes->item(3)->nodeName);
        self::assertSame('Activity', $paragraph->childNodes->item(1)->getAttribute('text:name'));
        self::assertSame('Activity', $paragraph->childNodes->item(3)->getAttribute('text:name'));
    }

    public function testMultipleExpressionsAreProcessedInReverseOrder(): void
    {
        $dom = $this->document('<text:p>{{firstname}} {{lastname}} {{firstname}}</text:p>');

        (new TemplateProcessor())->replaceScalarTextInSubtree($dom, ['firstname' => 'Walter', 'lastname' => 'Dietz'], static fn (string $filter, mixed $value, ?string $option): string => (string) $value);

        self::assertSame('Walter Dietz Walter', $dom->documentElement->firstChild->textContent);
    }

    private function document(string $children): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML('<root xmlns:text="' . self::TEXT . '">' . $children . '</root>');
        return $dom;
    }
}
