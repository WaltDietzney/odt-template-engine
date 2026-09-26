<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * @internal Ordered logical structure of one native Writer table.
 */
final readonly class LogicalTableStructure
{
    /**
     * @param list<LogicalTableRow> $rows
     */
    public function __construct(private array $rows)
    {
    }

    /** @return list<LogicalTableRow> */
    public function rows(): array
    {
        return $this->rows;
    }

    /** @return list<LogicalTableRow> */
    public function headerRows(): array
    {
        return array_values(array_filter(
            $this->rows,
            static fn (LogicalTableRow $row): bool => $row->isHeader()
        ));
    }

    /** @return list<LogicalTableRow> */
    public function ordinaryRows(): array
    {
        return array_values(array_filter(
            $this->rows,
            static fn (LogicalTableRow $row): bool => !$row->isHeader()
        ));
    }

    public function columnCount(): ?int
    {
        $first = $this->rows[0] ?? null;

        return $first?->logicalCellCount();
    }
}
