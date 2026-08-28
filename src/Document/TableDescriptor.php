<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Immutable snapshot of a named native ODF table.
 */
final readonly class TableDescriptor
{
    /** @param list<InspectionDiagnostic> $diagnostics */
    public function __construct(
        private string $name,
        private string $documentPart,
        private int $rowCount,
        private ?int $columnCount,
        private ?string $containingSection,
        private array $diagnostics = []
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function documentPart(): string
    {
        return $this->documentPart;
    }

    public function rowCount(): int
    {
        return $this->rowCount;
    }

    public function columnCount(): ?int
    {
        return $this->columnCount;
    }

    public function containingSection(): ?string
    {
        return $this->containingSection;
    }

    /** @return list<InspectionDiagnostic> */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => 'table',
            'name' => $this->name,
            'document_part' => $this->documentPart,
            'row_count' => $this->rowCount,
            'column_count' => $this->columnCount,
            'containing_section' => $this->containingSection,
            'diagnostics' => array_map(
                static fn (InspectionDiagnostic $diagnostic): array => $diagnostic->toArray(),
                $this->diagnostics
            ),
        ];
    }
}
