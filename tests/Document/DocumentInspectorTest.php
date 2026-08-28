<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Tests\Document;

use DOMDocument;
use DOMElement;
use OdtTemplateEngine\Document\BookmarkDescriptor;
use OdtTemplateEngine\Document\DocumentInspection;
use OdtTemplateEngine\Document\DocumentInspector;
use OdtTemplateEngine\Document\InspectionDiagnostic;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;

final class DocumentInspectorTest extends TestCase
{
    private const DRAWING_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TABLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testEmptyDocumentProducesTypedEmptySnapshot(): void
    {
        $inspection = $this->inspect($this->document());

        self::assertInstanceOf(DocumentInspection::class, $inspection);
        self::assertSame([], $inspection->sections());
        self::assertSame([], $inspection->bookmarks());
        self::assertSame([], $inspection->tables());
        self::assertSame([], $inspection->frames());
        self::assertSame([], $inspection->diagnostics());
    }

    public function testDiscoversSectionWithNestedNamedTableAndImageFrame(): void
    {
        $dom = $this->document();
        $section = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:section');
        $section->setAttribute('text:name', 'ExperienceEntry');
        $section->appendChild($this->paragraph($dom, 'Experience'));

        $table = $dom->createElementNS(self::TABLE_NAMESPACE, 'table:table');
        $table->setAttribute('table:name', 'Skills');
        $rows = $dom->createElementNS(self::TABLE_NAMESPACE, 'table:table-rows');
        $row = $dom->createElementNS(self::TABLE_NAMESPACE, 'table:table-row');
        $row->appendChild($dom->createElementNS(self::TABLE_NAMESPACE, 'table:table-cell'));
        $row->appendChild($dom->createElementNS(self::TABLE_NAMESPACE, 'table:covered-table-cell'));
        $rows->appendChild($row);
        $table->appendChild($rows);
        $section->appendChild($table);

        $frame = $dom->createElementNS(self::DRAWING_NAMESPACE, 'draw:frame');
        $frame->setAttribute('draw:name', 'ProfilePhoto');
        $frame->setAttribute('svg:width', '4cm');
        $frame->setAttribute('svg:height', '5cm');
        $frame->appendChild($dom->createElementNS(self::DRAWING_NAMESPACE, 'draw:image'));
        $section->appendChild($frame);
        $dom->documentElement->appendChild($section);

        $inspection = $this->inspect($dom);
        $sectionDescriptor = $inspection->section('ExperienceEntry');
        $tableDescriptor = $inspection->table('Skills');
        $frameDescriptor = $inspection->frame('ProfilePhoto');

        self::assertNotNull($sectionDescriptor);
        self::assertSame(1, $sectionDescriptor->childSummary()['paragraphs']);
        self::assertSame(1, $sectionDescriptor->childSummary()['tables']);
        self::assertSame(1, $sectionDescriptor->childSummary()['frames']);
        self::assertSame(['table', 'Skills'], [
            $sectionDescriptor->nestedNamedObjects()[0]->type(),
            $sectionDescriptor->nestedNamedObjects()[0]->name(),
        ]);
        self::assertNotNull($tableDescriptor);
        self::assertSame(1, $tableDescriptor->rowCount());
        self::assertSame(2, $tableDescriptor->columnCount());
        self::assertSame('ExperienceEntry', $tableDescriptor->containingSection());
        self::assertNotNull($frameDescriptor);
        self::assertSame('image', $frameDescriptor->payloadType());
        self::assertSame('4cm', $frameDescriptor->width());
        self::assertSame('ExperienceEntry', $frameDescriptor->containingSection());
    }

    public function testClassifiesBookmarkFormsAndSafeInlineText(): void
    {
        $dom = $this->document();
        $paragraph = $this->paragraph($dom, 'Hello ');
        $start = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-start');
        $start->setAttribute('text:name', 'FirstName');
        $end = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-end');
        $end->setAttribute('text:name', 'FirstName');
        $paragraph->appendChild($start);
        $paragraph->appendChild($dom->createTextNode('Walter'));
        $paragraph->appendChild($end);
        $dom->documentElement->appendChild($paragraph);

        $collapsed = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark');
        $collapsed->setAttribute('text:name', 'Cursor');
        $dom->documentElement->appendChild($collapsed);

        $inspection = $this->inspect($dom);
        self::assertSame(BookmarkDescriptor::TOPOLOGY_INLINE, $inspection->bookmark('FirstName')?->topology());
        self::assertSame('Walter', $inspection->bookmark('FirstName')?->text());
        self::assertSame(BookmarkDescriptor::TOPOLOGY_COLLAPSED, $inspection->bookmark('Cursor')?->topology());
        self::assertSame('', $inspection->bookmark('Cursor')?->text());
    }

    public function testClassifiesListAndTableSpanningBookmarkRanges(): void
    {
        $dom = $this->document();
        $list = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:list');
        $firstItem = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:list-item');
        $start = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-start');
        $start->setAttribute('text:name', 'ListRange');
        $firstItem->appendChild($this->paragraph($dom, 'Alpha', $start));
        $secondItem = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:list-item');
        $end = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-end');
        $end->setAttribute('text:name', 'ListRange');
        $secondItem->appendChild($this->paragraph($dom, 'Gamma', null, $end));
        $list->appendChild($firstItem);
        $list->appendChild($secondItem);
        $dom->documentElement->appendChild($list);

        $tableStart = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-start');
        $tableStart->setAttribute('text:name', 'TableRange');
        $dom->documentElement->appendChild($this->paragraph($dom, 'Before', $tableStart));
        $table = $dom->createElementNS(self::TABLE_NAMESPACE, 'table:table');
        $table->setAttribute('table:name', 'InsideRange');
        $dom->documentElement->appendChild($table);
        $tableEnd = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-end');
        $tableEnd->setAttribute('text:name', 'TableRange');
        $dom->documentElement->appendChild($this->paragraph($dom, 'After', null, $tableEnd));

        $inspection = $this->inspect($dom);
        self::assertSame(BookmarkDescriptor::TOPOLOGY_LIST_SPANNING, $inspection->bookmark('ListRange')?->topology());
        self::assertSame(BookmarkDescriptor::TOPOLOGY_MIXED_BLOCK, $inspection->bookmark('TableRange')?->topology());
        self::assertStringContainsString('Before', $inspection->bookmark('TableRange')?->text() ?? '');
        self::assertStringContainsString('After', $inspection->bookmark('TableRange')?->text() ?? '');
    }

    public function testClassifiesRangeAcrossParagraphsWithoutHigherRiskStructure(): void
    {
        $dom = $this->document();
        $start = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-start');
        $start->setAttribute('text:name', 'ParagraphRange');
        $end = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-end');
        $end->setAttribute('text:name', 'ParagraphRange');
        $dom->documentElement->appendChild($this->paragraph($dom, 'One', $start));
        $dom->documentElement->appendChild($this->paragraph($dom, 'Two', null, $end));

        $descriptor = $this->inspect($dom)->bookmark('ParagraphRange');

        self::assertNotNull($descriptor);
        self::assertSame(BookmarkDescriptor::TOPOLOGY_PARAGRAPH_SPANNING, $descriptor->topology());
        self::assertSame('OneTwo', $descriptor->text());
    }

    public function testReportsDuplicateAndMalformedNativeIdentitiesWithoutGlobalNameCollision(): void
    {
        $dom = $this->document();
        foreach (['Duplicate', 'Duplicate'] as $name) {
            $section = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:section');
            $section->setAttribute('text:name', $name);
            $dom->documentElement->appendChild($section);
        }
        $table = $dom->createElementNS(self::TABLE_NAMESPACE, 'table:table');
        $table->setAttribute('table:name', 'Duplicate');
        $dom->documentElement->appendChild($table);
        $frame = $dom->createElementNS(self::DRAWING_NAMESPACE, 'draw:frame');
        $frame->setAttribute('draw:name', 'Duplicate');
        $dom->documentElement->appendChild($frame);

        $unpaired = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark-start');
        $unpaired->setAttribute('text:name', 'Broken');
        $dom->documentElement->appendChild($unpaired);

        $inspection = $this->inspect($dom);
        $codes = array_map(
            static fn (InspectionDiagnostic $diagnostic): string => $diagnostic->code(),
            $inspection->diagnostics()
        );

        self::assertContains('duplicate_native_name', $codes);
        self::assertContains('unpaired_bookmark_marker', $codes);
        self::assertNotNull($inspection->section('Duplicate'));
        self::assertNotNull($inspection->table('Duplicate'));
        self::assertNotNull($inspection->frame('Duplicate'));
        self::assertSame(BookmarkDescriptor::TOPOLOGY_MALFORMED, $inspection->bookmark('Broken')?->topology());
        self::assertNotContains('global_name_collision', $codes);
    }

    public function testReportsMissingNamesAndProducesStableMachineReadableOutput(): void
    {
        $dom = $this->document();
        $dom->documentElement->appendChild($dom->createElementNS(self::TEXT_NAMESPACE, 'text:section'));
        $dom->documentElement->appendChild($dom->createElementNS(self::TABLE_NAMESPACE, 'table:table'));
        $dom->documentElement->appendChild($dom->createElementNS(self::DRAWING_NAMESPACE, 'draw:frame'));
        $bookmark = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:bookmark');
        $dom->documentElement->appendChild($bookmark);

        $first = $this->inspect($dom);
        $second = $this->inspect($dom);

        self::assertSame($first->toArray(), $second->toArray());
        self::assertCount(4, $first->diagnostics());
        self::assertIsArray($first->toArray());
    }

    public function testFacadeCreatesFreshReadOnlySnapshotsAcrossLifecycleReset(): void
    {
        $template = new OdtTemplate('samples/templates/template_01_simple_variables.odt');
        $before = $template->inspect();
        $template->load();
        $after = $template->inspect();

        self::assertNotSame($before, $after);
        self::assertSame($before->toArray(), $after->toArray());
    }

    private function inspect(DOMDocument $content): DocumentInspection
    {
        return (new DocumentInspector())->inspect($content, $this->document());
    }

    private function document(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createElementNS(self::OFFICE_NAMESPACE, 'office:document-content'));

        return $dom;
    }

    private function paragraph(
        DOMDocument $dom,
        string $text,
        ?DOMElement $before = null,
        ?DOMElement $after = null
    ): DOMElement {
        $paragraph = $dom->createElementNS(self::TEXT_NAMESPACE, 'text:p');
        if ($before !== null) {
            $paragraph->appendChild($before);
        }
        $paragraph->appendChild($dom->createTextNode($text));
        if ($after !== null) {
            $paragraph->appendChild($after);
        }

        return $paragraph;
    }
}
