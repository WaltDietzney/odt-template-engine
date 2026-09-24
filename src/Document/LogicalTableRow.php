<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;

/**
 * @internal Logical view of one native table row for bounded inspection and
 * future TABLE-ROW-01 mutation. The owning DOM remains authoritative.
 */
final readonly class LogicalTableRow
{
    /**
     * @param list<DOMElement> $cells
     */
    public function __construct(
        private DOMElement $element,
        private bool $header,
        private array $cells
    ) {
    }

    public function element(): DOMElement
    {
        return $this->element;
    }

    public function isHeader(): bool
    {
        return $this->header;
    }

    /** @return list<DOMElement> */
    public function cells(): array
    {
        return $this->cells;
    }

    public function logicalCellCount(): int
    {
        $count = 0;
        foreach ($this->cells as $cell) {
            $count += max(1, (int) ($cell->getAttribute('table:number-columns-repeated') ?: 1));
        }

        return $count;
    }
}
