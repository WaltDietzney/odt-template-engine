<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;

/** @internal Source-row baseline for repeated population of one working table. */
final readonly class NativeTablePopulationState
{
    /**
     * @param list<DOMElement> $ordinaryRows
     */
    public function __construct(
        private array $ordinaryRows,
        private string $containerNodeName,
        private int $containerOrdinal
    ) {
    }

    /** @return list<DOMElement> */
    public function ordinaryRows(): array
    {
        return $this->ordinaryRows;
    }

    public function containerNodeName(): string
    {
        return $this->containerNodeName;
    }

    public function containerOrdinal(): int
    {
        return $this->containerOrdinal;
    }
}
