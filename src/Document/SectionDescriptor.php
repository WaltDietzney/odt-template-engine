<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Immutable snapshot of a named ODF section.
 */
final readonly class SectionDescriptor
{
    /** @param array<string, int> $childSummary
     *  @param list<NamedObjectReference> $nestedNamedObjects
     *  @param list<InspectionDiagnostic> $diagnostics
     */
    public function __construct(
        private string $name,
        private string $documentPart,
        private array $childSummary,
        private array $nestedNamedObjects,
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

    /** @return array<string, int> */
    public function childSummary(): array
    {
        return $this->childSummary;
    }

    /** @return list<NamedObjectReference> */
    public function nestedNamedObjects(): array
    {
        return $this->nestedNamedObjects;
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
            'type' => 'section',
            'name' => $this->name,
            'document_part' => $this->documentPart,
            'child_summary' => $this->childSummary,
            'nested_named_objects' => array_map(
                static fn (NamedObjectReference $reference): array => $reference->toArray(),
                $this->nestedNamedObjects
            ),
            'diagnostics' => array_map(
                static fn (InspectionDiagnostic $diagnostic): array => $diagnostic->toArray(),
                $this->diagnostics
            ),
        ];
    }
}
