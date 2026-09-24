<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;

/**
 * @internal Reads the supported native Writer table row containers in source
 * order for inspection and the future bounded table population operation.
 */
final class LogicalTableStructureReader
{
    private const TABLE_CELL = 'table:table-cell';
    private const TABLE_HEADER_ROWS = 'table:table-header-rows';
    private const TABLE_ROW = 'table:table-row';
    private const TABLE_ROWS = 'table:table-rows';
    private const COVERED_TABLE_CELL = 'table:covered-table-cell';

    public function read(DOMElement $table): LogicalTableStructure
    {
        $rows = [];
        foreach ($table->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            if ($child->nodeName === self::TABLE_ROW) {
                $rows[] = $this->row($child, false);
                continue;
            }

            if (!in_array($child->nodeName, [self::TABLE_ROWS, self::TABLE_HEADER_ROWS], true)) {
                continue;
            }

            $isHeader = $child->nodeName === self::TABLE_HEADER_ROWS;
            foreach ($child->childNodes as $row) {
                if ($row instanceof DOMElement && $row->nodeName === self::TABLE_ROW) {
                    $rows[] = $this->row($row, $isHeader);
                }
            }
        }

        return new LogicalTableStructure($rows);
    }

    private function row(DOMElement $row, bool $header): LogicalTableRow
    {
        $cells = [];
        foreach ($row->childNodes as $cell) {
            if ($cell instanceof DOMElement
                && in_array($cell->nodeName, [self::TABLE_CELL, self::COVERED_TABLE_CELL], true)
            ) {
                $cells[] = $cell;
            }
        }

        return new LogicalTableRow($row, $header, $cells);
    }
}
