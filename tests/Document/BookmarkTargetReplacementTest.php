<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\BookmarkMutationException;
use OdtTemplateEngine\Document\BookmarkTarget;
use OdtTemplateEngine\Document\MalformedTargetException;
use OdtTemplateEngine\Document\TargetNotFoundException;
use OdtTemplateEngine\Document\TypedTargetResolver;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class BookmarkTargetReplacementTest extends TestCase
{
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testReplacesPlainInlineTextAndPreservesMarkersAndIdentity(): void
    {
        [$context, $dom] = $this->contextWithRange('Before', 'Max', 'After');
        $target = $this->target($context, 'Name');

        self::assertSame('Max', $target->descriptor()->text());
        self::assertSame($target, $target->replaceText('Walter'));
        self::assertSame('Walter', $target->descriptor()->text());
        self::assertCount(1, $this->markers($dom, 'bookmark-start', 'Name'));
        self::assertCount(1, $this->markers($dom, 'bookmark-end', 'Name'));
        self::assertSame('BeforeWalterAfter', $dom->textContent);
    }

    public function testReplacesTextInsideOneStyledSpanWithoutRemovingWrapper(): void
    {
        [$context, $dom] = $this->contextWithRange('Before', 'Max', 'After', true);
        $target = $this->target($context, 'Name');
        $span = $dom->getElementsByTagNameNS(self::TEXT, 'span')->item(0);

        $target->replaceText('Walter');

        self::assertInstanceOf(DOMElement::class, $span);
        self::assertSame('A', $span->getAttribute('text:style-name'));
        self::assertSame('Walter', $target->descriptor()->text());
        self::assertCount(1, $this->markers($dom, 'bookmark-start', 'Name'));
        self::assertCount(1, $this->markers($dom, 'bookmark-end', 'Name'));
    }

    public function testReplacementTreatsXmlSpecialCharactersAsLiteralText(): void
    {
        [$context, $dom] = $this->contextWithRange('', 'Max', '');
        $target = $this->target($context, 'Name');

        $target->replaceText('& < >');

        self::assertSame('& < >', $target->descriptor()->text());
        $xml = $dom->saveXML();
        self::assertStringContainsString('&amp; &lt; &gt;', $xml);
    }

    public function testRepeatedReplacementAndInspectionRemainStable(): void
    {
        [$context] = $this->contextWithRange('', 'Max', '');
        $target = $this->target($context, 'Name');

        $target->replaceText('Walter')->replaceText('Max');

        self::assertSame('Max', $target->descriptor()->text());
    }

    /** @dataProvider unsupportedRangeProvider */
    public function testUnsupportedRangesFailAtomically(string $shape): void
    {
        [$context, $dom] = $this->contextForShape($shape);
        $target = $this->target($context, 'Name');
        $before = $dom->saveXML();
        $beforeText = $target->descriptor()->text();

        try {
            $target->replaceText('Walter');
            self::fail('Expected bookmark mutation to be rejected.');
        } catch (BookmarkMutationException $exception) {
            self::assertSame('Name', $exception->bookmarkName());
            self::assertSame('replaceText', $exception->operation());
            self::assertNotSame('', $exception->reason());
        }

        self::assertSame($before, $dom->saveXML());
        self::assertSame($beforeText, $target->descriptor()->text());
    }

    /** @return \Generator<string, array{string}> */
    public static function unsupportedRangeProvider(): \Generator
    {
        yield 'empty' => ['empty'];
        yield 'collapsed' => ['collapsed'];
        yield 'around span' => ['around-span'];
        yield 'multiple spans' => ['multiple-spans'];
        yield 'paragraph spanning' => ['paragraph-spanning'];
        yield 'list spanning' => ['list-spanning'];
        yield 'table spanning' => ['table-spanning'];
        yield 'mixed block' => ['mixed-block'];
    }

    public function testUnsupportedReplacementValuesFailBeforeDomMutation(): void
    {
        [$context, $dom] = $this->contextWithRange('', 'Max', '');
        $target = $this->target($context, 'Name');
        $before = $dom->saveXML();

        foreach (["line\nbreak", "tab\tvalue", ' leading', 'trailing ', 'two  spaces'] as $value) {
            try {
                $target->replaceText($value);
                self::fail('Expected replacement value to be rejected.');
            } catch (BookmarkMutationException $exception) {
                self::assertSame('replaceText', $exception->operation());
            }
            self::assertSame($before, $dom->saveXML());
        }
    }

    public function testReplacementDoesNotInvokeTemplateProcessing(): void
    {
        [$context] = $this->contextWithRange('', '{{firstName}}', '');
        $target = $this->target($context, 'Name');

        $target->replaceText('Walter');

        self::assertSame('Walter', $target->descriptor()->text());
    }

    public function testPublicFacadeReplacementSurvivesRenderSaveAndReopen(): void
    {
        $template = new class ('samples/templates/template_01_simple_variables.odt') extends OdtTemplate {
            public function addBookmark(string $value): void
            {
                $dom = $this->documentContext()->contentDom();
                $text = $dom->getElementsByTagNameNS(
                    'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
                    'text'
                )->item(0);
                $textNamespace = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
                $paragraph = $dom->createElementNS($textNamespace, 'text:p');
                $start = $dom->createElementNS($textNamespace, 'text:bookmark-start');
                $start->setAttribute('text:name', 'Name');
                $end = $dom->createElementNS($textNamespace, 'text:bookmark-end');
                $end->setAttribute('text:name', 'Name');
                $paragraph->appendChild($start);
                $paragraph->appendChild($dom->createTextNode($value));
                $paragraph->appendChild($end);
                $text?->appendChild($paragraph);
            }
        };
        $template->addBookmark('Max');
        $template->bookmark('Name')->replaceText('Walter');
        $template->render();

        $output = tempnam(sys_get_temp_dir(), 'odt-bookmark-') . '.odt';
        $template->save($output);
        $zip = new ZipArchive();
        self::assertSame(true, $zip->open($output));
        self::assertSame(true, $zip->locateName('content.xml') !== false);
        self::assertSame(true, $zip->locateName('META-INF/manifest.xml') !== false);
        foreach (['content.xml', 'styles.xml', 'meta.xml', 'META-INF/manifest.xml'] as $part) {
            $xml = $zip->getFromName($part);
            self::assertIsString($xml);
            $parsed = new DOMDocument();
            self::assertTrue($parsed->loadXML($xml));
        }
        $zip->close();

        $reopened = new OdtTemplate($output);
        self::assertSame('Walter', $reopened->bookmark('Name')->descriptor()->text());
        $reopened->bookmark('Name')->replaceText('Max');
        self::assertSame('Max', $reopened->bookmark('Name')->descriptor()->text());
        unlink($output);
    }

    public function testIdentityBackedTargetUsesCurrentDocumentAfterContextReplacement(): void
    {
        [$context] = $this->contextWithRange('', 'Old', '');
        $target = $this->target($context, 'Name');
        [, $replacement] = $this->contextWithRange('', 'New', '');
        $context->replaceCoreDocuments($replacement, $this->document(), $this->document());

        $target->replaceText('Current');

        self::assertSame('Current', $target->descriptor()->text());
    }

    public function testMalformedAndCollapsedBookmarksUseResolutionOrMutationErrors(): void
    {
        [$context] = $this->contextForShape('collapsed');
        try {
            $this->target($context, 'Name')->replaceText('Walter');
            self::fail('Collapsed bookmarks should resolve as read-only targets but reject mutation.');
        } catch (BookmarkMutationException|MalformedTargetException $exception) {
            self::assertTrue(true);
        }

        [$malformed] = $this->contextForShape('malformed');
        $this->expectException(MalformedTargetException::class);
        $this->target($malformed, 'Name');
    }

    public function testMissingBookmarkRemainsStrictlyNotFound(): void
    {
        $this->expectException(TargetNotFoundException::class);
        $this->target($this->contextForShape('plain')[0], 'Missing');
    }

    private function target(OdtDocumentContext $context, string $name): BookmarkTarget
    {
        return (new TypedTargetResolver())->resolveBookmark($context, $name);
    }

    /** @return array{OdtDocumentContext, DOMDocument} */
    private function contextWithRange(string $before, string $text, string $after, bool $styled = false): array
    {
        $dom = $this->document();
        $paragraph = $dom->createElementNS(self::TEXT, 'text:p');
        $paragraph->appendChild($dom->createTextNode($before));
        $start = $this->marker($dom, 'bookmark-start');
        $end = $this->marker($dom, 'bookmark-end');
        if ($styled) {
            $span = $dom->createElementNS(self::TEXT, 'text:span');
            $span->setAttribute('text:style-name', 'A');
            $span->appendChild($start);
            $span->appendChild($dom->createTextNode($text));
            $span->appendChild($end);
            $paragraph->appendChild($span);
        } else {
            $paragraph->appendChild($start);
            $paragraph->appendChild($dom->createTextNode($text));
            $paragraph->appendChild($end);
        }
        $paragraph->appendChild($dom->createTextNode($after));
        $dom->documentElement->appendChild($paragraph);

        return [new OdtDocumentContext($dom, $this->document(), $this->document()), $dom];
    }

    /** @return array{OdtDocumentContext, DOMDocument} */
    private function contextForShape(string $shape): array
    {
        if ($shape === 'plain') {
            return $this->contextWithRange('', 'Max', '');
        }
        $dom = $this->document();
        if ($shape === 'collapsed') {
            $marker = $this->marker($dom, 'bookmark');
            $dom->documentElement->appendChild($marker);
        } elseif ($shape === 'malformed') {
            $dom->documentElement->appendChild($this->marker($dom, 'bookmark-start'));
        } elseif ($shape === 'empty') {
            [$start, $end] = $this->namedPair($dom);
            $paragraph = $this->paragraph($dom);
            $paragraph->appendChild($start);
            $paragraph->appendChild($end);
            $dom->documentElement->appendChild($paragraph);
        } elseif ($shape === 'around-span') {
            [$start, $end] = $this->namedPair($dom);
            $paragraph = $this->paragraph($dom);
            $span = $dom->createElementNS(self::TEXT, 'text:span');
            $span->appendChild($dom->createTextNode('Max'));
            $paragraph->appendChild($start);
            $paragraph->appendChild($span);
            $paragraph->appendChild($end);
            $dom->documentElement->appendChild($paragraph);
        } elseif ($shape === 'multiple-spans') {
            [$start, $end] = $this->namedPair($dom);
            $paragraph = $this->paragraph($dom);
            $paragraph->appendChild($start);
            foreach (['A', 'B'] as $style) {
                $span = $dom->createElementNS(self::TEXT, 'text:span');
                $span->setAttribute('text:style-name', $style);
                $span->appendChild($dom->createTextNode('Max'));
                $paragraph->appendChild($span);
            }
            $paragraph->appendChild($end);
            $dom->documentElement->appendChild($paragraph);
        } else {
            [$start, $end] = $this->namedPair($dom);
            $first = $this->paragraph($dom, 'Max', $start);
            $second = $this->paragraph($dom, 'After', null, $end);
            if ($shape === 'list-spanning') {
                $list = $dom->createElementNS(self::TEXT, 'text:list');
                $item1 = $dom->createElementNS(self::TEXT, 'text:list-item');
                $item1->appendChild($first);
                $item2 = $dom->createElementNS(self::TEXT, 'text:list-item');
                $item2->appendChild($second);
                $list->appendChild($item1);
                $list->appendChild($item2);
                $dom->documentElement->appendChild($list);
            } elseif ($shape === 'table-spanning') {
                $table = $dom->createElementNS('urn:oasis:names:tc:opendocument:xmlns:table:1.0', 'table:table');
                $dom->documentElement->appendChild($first);
                $dom->documentElement->appendChild($table);
                $dom->documentElement->appendChild($second);
            } else {
                $dom->documentElement->appendChild($first);
                $dom->documentElement->appendChild($second);
            }
        }

        return [new OdtDocumentContext($dom, $this->document(), $this->document()), $dom];
    }

    private function document(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(sprintf(
            '<office:document-content xmlns:office="%s" xmlns:text="%s"/>',
            self::OFFICE,
            self::TEXT
        ));
        return $dom;
    }

    private function marker(DOMDocument $dom, string $localName): DOMElement
    {
        $marker = $dom->createElementNS(self::TEXT, 'text:' . $localName);
        $marker->setAttribute('text:name', 'Name');
        return $marker;
    }

    /** @return array{DOMElement, DOMElement} */
    private function namedPair(DOMDocument $dom): array
    {
        return [$this->marker($dom, 'bookmark-start'), $this->marker($dom, 'bookmark-end')];
    }

    private function paragraph(DOMDocument $dom, string $text = '', ?DOMElement $start = null, ?DOMElement $end = null): DOMElement
    {
        $paragraph = $dom->createElementNS(self::TEXT, 'text:p');
        if ($start !== null) {
            $paragraph->appendChild($start);
        }
        $paragraph->appendChild($dom->createTextNode($text));
        if ($end !== null) {
            $paragraph->appendChild($end);
        }
        return $paragraph;
    }

    /** @return list<DOMElement> */
    private function markers(DOMDocument $dom, string $localName, string $name): array
    {
        $result = [];
        foreach ($dom->getElementsByTagNameNS(self::TEXT, $localName) as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
                $result[] = $node;
            }
        }
        return $result;
    }
}
