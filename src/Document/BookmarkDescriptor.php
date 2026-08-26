<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Immutable snapshot of a native bookmark or bookmark range.
 */
final readonly class BookmarkDescriptor
{
    public const TOPOLOGY_COLLAPSED = 'collapsed';
    public const TOPOLOGY_INLINE = 'inline';
    public const TOPOLOGY_PARAGRAPH_SPANNING = 'paragraph_spanning';
    public const TOPOLOGY_LIST_SPANNING = 'list_spanning';
    public const TOPOLOGY_TABLE_SPANNING = 'table_spanning';
    public const TOPOLOGY_MIXED_BLOCK = 'mixed_block';
    public const TOPOLOGY_MALFORMED = 'malformed';

    /** @param list<InspectionDiagnostic> $diagnostics */
    public function __construct(
        private string $name,
        private string $documentPart,
        private bool $hasStart,
        private bool $hasEnd,
        private string $topology,
        private ?string $text,
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

    public function hasStart(): bool
    {
        return $this->hasStart;
    }

    public function hasEnd(): bool
    {
        return $this->hasEnd;
    }

    public function topology(): string
    {
        return $this->topology;
    }

    public function text(): ?string
    {
        return $this->text;
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
            'type' => 'bookmark',
            'name' => $this->name,
            'document_part' => $this->documentPart,
            'has_start' => $this->hasStart,
            'has_end' => $this->hasEnd,
            'topology' => $this->topology,
            'text' => $this->text,
            'diagnostics' => array_map(
                static fn (InspectionDiagnostic $diagnostic): array => $diagnostic->toArray(),
                $this->diagnostics
            ),
        ];
    }
}
