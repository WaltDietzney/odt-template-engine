<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\SectionTarget;
use OdtTemplateEngine\Document\TargetNotFoundException;
use OdtTemplateEngine\Document\TypedTargetResolver;
use OdtTemplateEngine\OdtDocumentContext;
use PHPUnit\Framework\TestCase;

final class SectionTargetReadTest extends TestCase
{
    private const DRAWING = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TABLE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testTextViewUsesDocumentOrderAndLineSeparatedTextBlocks(): void
    {
        [$context] = $this->contextWithSection();
        $target = $this->target($context);

        self::assertSame("Inner text\nExperience\nBuilds documents\nAlpha\nBeta\nA1\nA2\nBox text\nCompany", $target->text());
    }

    public function testDescriptorAndConvenienceReadExposeNestedNamedObjects(): void
    {
        [$context] = $this->contextWithSection();
        $objects = $this->target($context)->nestedNamedObjects();

        self::assertSame(
            [
                ['section', 'Inner'],
                ['table', 'Skills'],
                ['frame', 'Logo'],
                ['bookmark', 'Company'],
            ],
            array_map(static fn ($object): array => [$object->type(), $object->name()], $objects)
        );
    }

    public function testInnerSectionRemainsIndependentlyResolvable(): void
    {
        [$context] = $this->contextWithSection();
        $inner = (new TypedTargetResolver())->resolveSection($context, 'Inner');

        self::assertSame('Inner text', $inner->text());
    }

    public function testReadOperationsDoNotMutateDocumentDom(): void
    {
        [$context] = $this->contextWithSection();
        $before = $context->contentDom()->saveXML();
        $target = $this->target($context);

        $target->descriptor();
        $target->text();
        $target->nestedNamedObjects();

        self::assertSame($before, $context->contentDom()->saveXML());
    }

    public function testEmptySectionHasAnEmptyTextView(): void
    {
        $dom = $this->document();
        $section = $dom->createElementNS(self::TEXT, 'text:section');
        $section->setAttribute('text:name', 'Empty');
        $dom->documentElement->appendChild($section);
        $context = new OdtDocumentContext($dom, $this->document(), $this->document());

        self::assertSame('', (new TypedTargetResolver())->resolveSection($context, 'Empty')->text());
    }

    public function testMissingSectionUsesStrictResolution(): void
    {
        $this->expectException(TargetNotFoundException::class);
        $this->target(new OdtDocumentContext($this->document(), $this->document(), $this->document()), 'Missing');
    }

    private function target(OdtDocumentContext $context, string $name = 'ExperienceEntry'): SectionTarget
    {
        return (new TypedTargetResolver())->resolveSection($context, $name);
    }

    /** @return array{OdtDocumentContext, DOMDocument} */
    private function contextWithSection(): array
    {
        $dom = $this->document();
        $section = $dom->createElementNS(self::TEXT, 'text:section');
        $section->setAttribute('text:name', 'ExperienceEntry');
        $section->appendChild($this->paragraph($dom, 'Experience'));
        $section->appendChild($this->paragraph($dom, 'Builds documents'));

        $list = $dom->createElementNS(self::TEXT, 'text:list');
        foreach (['Alpha', 'Beta'] as $value) {
            $item = $dom->createElementNS(self::TEXT, 'text:list-item');
            $item->appendChild($this->paragraph($dom, $value));
            $list->appendChild($item);
        }
        $section->appendChild($list);

        $table = $dom->createElementNS(self::TABLE, 'table:table');
        $table->setAttribute('table:name', 'Skills');
        $rows = $dom->createElementNS(self::TABLE, 'table:table-rows');
        $row = $dom->createElementNS(self::TABLE, 'table:table-row');
        foreach (['A1', 'A2'] as $value) {
            $cell = $dom->createElementNS(self::TABLE, 'table:table-cell');
            $cell->appendChild($this->paragraph($dom, $value));
            $row->appendChild($cell);
        }
        $rows->appendChild($row);
        $table->appendChild($rows);
        $section->appendChild($table);

        $frame = $dom->createElementNS(self::DRAWING, 'draw:frame');
        $frame->setAttribute('draw:name', 'Logo');
        $box = $dom->createElementNS(self::DRAWING, 'draw:text-box');
        $box->appendChild($this->paragraph($dom, 'Box text'));
        $frame->appendChild($box);
        $section->appendChild($frame);

        $inner = $dom->createElementNS(self::TEXT, 'text:section');
        $inner->setAttribute('text:name', 'Inner');
        $inner->appendChild($this->paragraph($dom, 'Inner text'));
        $section->insertBefore($inner, $section->firstChild);

        $bookmarkStart = $dom->createElementNS(self::TEXT, 'text:bookmark-start');
        $bookmarkStart->setAttribute('text:name', 'Company');
        $bookmarkEnd = $dom->createElementNS(self::TEXT, 'text:bookmark-end');
        $bookmarkEnd->setAttribute('text:name', 'Company');
        $company = $this->paragraph($dom, 'Company');
        $company->insertBefore($bookmarkStart, $company->firstChild);
        $company->appendChild($bookmarkEnd);
        $section->appendChild($company);

        $dom->documentElement->appendChild($section);
        return [new OdtDocumentContext($dom, $this->document(), $this->document()), $dom];
    }

    private function paragraph(DOMDocument $dom, string $text): DOMElement
    {
        $paragraph = $dom->createElementNS(self::TEXT, 'text:p');
        $paragraph->appendChild($dom->createTextNode($text));
        return $paragraph;
    }

    private function document(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(sprintf(
            '<office:document-content xmlns:office="%s" xmlns:text="%s" xmlns:table="%s" xmlns:draw="%s"/>',
            self::OFFICE,
            self::TEXT,
            self::TABLE,
            self::DRAWING
        ));
        return $dom;
    }
}
