<?php

declare(strict_types=1);

namespace OdtTemplateEngineTests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\Document\AmbiguousAddressableTargetException;
use OdtTemplateEngine\Document\DocumentInspector;
use OdtTemplateEngine\Document\TargetNotFoundException;
use OdtTemplateEngine\Document\TypedTargetResolver;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtTemplate;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * TABLE-ROW-01 Slice 0 characterization gate.
 *
 * These tests record current Writer/ODF evidence before native table
 * population is implemented. In particular, the direct-row assertion keeps
 * the known DocumentInspector undercount visible without changing production
 * behavior; correction is deferred to TABLE-ROW-01 Slice 1.
 */
final class TableRow01CharacterizationTest extends TestCase
{
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const TABLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function testDirectWriterRowsAreAddressableButCurrentlyUndercounted(): void
    {
        $path = $this->root() . '/tests/fixtures/libreoffice-reference/odt/TABLE-02-formatted-cell.odt';
        $template = new OdtTemplate($path);
        $table = $template->table('Tabelle1');
        $descriptor = $table->descriptor();
        $xpath = $this->partXPath($path, 'content.xml');

        self::assertSame(1, $xpath->query('//table:table[@table:name="Tabelle1"]/table:table-row')?->length);
        self::assertSame(0, $xpath->query('//table:table[@table:name="Tabelle1"]/table:table-rows')?->length);
        self::assertSame('Tabelle1', $descriptor->name());
        self::assertSame('content.xml', $descriptor->documentPart());

        // CURRENT BEHAVIOR: direct table:table-row is omitted by tableRows().
        // REQUIRED TABLE-ROW-01 BEHAVIOR: this valid Writer row counts as one
        // logical row. Slice 1 must correct the inspector before mutation.
        self::assertSame(0, $descriptor->rowCount());
        self::assertNull($descriptor->columnCount());
    }

    public function testGroupedBodyRowsAndNativeHeaderRowsRemainDistinguishable(): void
    {
        $path = $this->root() . '/samples/templates/template_L09_native_objects.odt';
        $template = new OdtTemplate($path);
        $descriptor = $template->table('ProjectMilestones')->descriptor();
        $xpath = $this->partXPath($path, 'content.xml');

        self::assertSame(1, $xpath->query('//table:table[@table:name="ProjectMilestones"]/table:table-header-rows/table:table-row')?->length);
        self::assertSame(2, $xpath->query('//table:table[@table:name="ProjectMilestones"]/table:table-rows/table:table-row')?->length);
        self::assertSame(3, $descriptor->rowCount());
        self::assertSame(2, $descriptor->columnCount());

        $headerCell = $xpath->query('//table:table[@table:name="ProjectMilestones"]/table:table-header-rows/table:table-row/table:table-cell[1]')->item(0);
        $bodyCell = $xpath->query('//table:table[@table:name="ProjectMilestones"]/table:table-rows/table:table-row[1]/table:table-cell[1]')->item(0);
        self::assertSame('F5HeaderCell', $headerCell instanceof DOMElement ? $headerCell->getAttribute('table:style-name') : null);
        self::assertSame('F5BodyCell', $bodyCell instanceof DOMElement ? $bodyCell->getAttribute('table:style-name') : null);
        self::assertSame('Milestone', trim($headerCell?->textContent ?? ''));
        self::assertSame('Design', trim($bodyCell?->textContent ?? ''));
    }

    public function testWriterAuthoredCellStructureCarriesTableRowCellAndParagraphStyles(): void
    {
        $path = $this->root() . '/tests/fixtures/libreoffice-reference/odt/TABLE-02-formatted-cell.odt';
        $xpath = $this->partXPath($path, 'content.xml');

        $table = $xpath->query('//table:table[@table:name="Tabelle1"]')->item(0);
        $row = $xpath->query('//table:table[@table:name="Tabelle1"]/table:table-row')->item(0);
        $cell = $xpath->query('//table:table[@table:name="Tabelle1"]/table:table-row/table:table-cell')->item(0);
        $paragraph = $xpath->query('//table:table[@table:name="Tabelle1"]/table:table-row/table:table-cell/text:p')->item(0);

        self::assertSame('Tabelle1', $table instanceof DOMElement ? $table->getAttribute('table:name') : null);
        self::assertSame('Tabelle1', $table instanceof DOMElement ? $table->getAttribute('table:style-name') : null);
        self::assertSame('Tabelle1.1', $row instanceof DOMElement ? $row->getAttribute('table:style-name') : null);
        self::assertSame('Tabelle1.A1', $cell instanceof DOMElement ? $cell->getAttribute('table:style-name') : null);
        self::assertSame('P1', $paragraph instanceof DOMElement ? $paragraph->getAttribute('text:style-name') : null);
        self::assertSame('Formatted cell text', trim($paragraph?->textContent ?? ''));
    }

    public function testLogicalColumnCountExpandsRepeatedAndCoveredCells(): void
    {
        $content = $this->document('<table:table table:name="Columns">'
            . '<table:table-rows><table:table-row>'
            . '<table:table-cell table:number-columns-repeated="2"/>'
            . '<table:covered-table-cell/>'
            . '</table:table-row></table:table-rows>'
            . '</table:table>');
        $styles = $this->document('');

        $descriptor = (new DocumentInspector())->inspect($content, $styles)->table('Columns');

        self::assertNotNull($descriptor);
        self::assertSame(1, $descriptor->rowCount());
        self::assertSame(3, $descriptor->columnCount());
    }

    public function testStrictNamedTableResolutionCoversPresentMissingAndDuplicateNames(): void
    {
        $present = new OdtTemplate(
            $this->root() . '/tests/fixtures/libreoffice-reference/odt/TABLE-02-formatted-cell.odt'
        );
        self::assertSame('Tabelle1', $present->table('Tabelle1')->descriptor()->name());

        $this->expectException(TargetNotFoundException::class);
        $present->table('MissingTable');
    }

    public function testDuplicateTableNamesAreAmbiguousForTypedResolution(): void
    {
        $content = $this->document(
            '<table:table table:name="DuplicateTable"/><table:table table:name="DuplicateTable"/>'
        );
        $context = new OdtDocumentContext($content, $this->document(''), $this->document(''));

        $this->expectException(AmbiguousAddressableTargetException::class);
        (new TypedTargetResolver())->resolveTable($context, 'DuplicateTable');
    }

    public function testWriterRowsProvideAnUnambiguousFutureKeepRowsSourceSequence(): void
    {
        $path = $this->root() . '/samples/templates/template_L09_native_objects.odt';
        $xpath = $this->partXPath($path, 'content.xml');
        $rows = $xpath->query('//table:table[@table:name="ProjectMilestones"]/table:table-rows/table:table-row');

        self::assertSame(2, $rows?->length);
        self::assertSame('Design', trim($xpath->query('table:table-cell[1]', $rows?->item(0))->item(0)?->textContent ?? ''));
        self::assertSame('Review', trim($xpath->query('table:table-cell[1]', $rows?->item(1))->item(0)?->textContent ?? ''));
        // Future keepRows indices address this ordinary source sequence only;
        // the native header row is intentionally outside it.
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function partXPath(string $path, string $part): DOMXPath
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName($part);
        $zip->close();
        self::assertIsString($xml);

        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($xml));

        return $this->xpath($dom);
    }

    private function document(string $body): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        self::assertTrue($dom->loadXML(
            '<office:document-content'
            . ' xmlns:office="' . self::OFFICE_NS . '"'
            . ' xmlns:table="' . self::TABLE_NS . '"'
            . ' xmlns:text="' . self::TEXT_NS . '"'
            . ' xmlns:style="' . self::STYLE_NS . '">' . $body
            . '</office:document-content>'
        ));

        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', self::OFFICE_NS);
        $xpath->registerNamespace('style', self::STYLE_NS);
        $xpath->registerNamespace('table', self::TABLE_NS);
        $xpath->registerNamespace('text', self::TEXT_NS);

        return $xpath;
    }
}
