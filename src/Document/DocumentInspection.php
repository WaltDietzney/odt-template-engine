<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Immutable inspection snapshot of native named ODF structures.
 */
final readonly class DocumentInspection
{
    /**
     * @param list<SectionDescriptor> $sections
     * @param list<BookmarkDescriptor> $bookmarks
     * @param list<TableDescriptor> $tables
     * @param list<FrameDescriptor> $frames
     * @param list<InspectionDiagnostic> $diagnostics
     */
    public function __construct(
        private array $sections,
        private array $bookmarks,
        private array $tables,
        private array $frames,
        private array $diagnostics = []
    ) {
    }

    /** @return list<SectionDescriptor> */
    public function sections(): array
    {
        return $this->sections;
    }

    /** @return list<BookmarkDescriptor> */
    public function bookmarks(): array
    {
        return $this->bookmarks;
    }

    /** @return list<TableDescriptor> */
    public function tables(): array
    {
        return $this->tables;
    }

    /** @return list<FrameDescriptor> */
    public function frames(): array
    {
        return $this->frames;
    }

    /** @return list<InspectionDiagnostic> */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    public function section(string $name): ?SectionDescriptor
    {
        return $this->firstNamed($this->sections, $name);
    }

    public function bookmark(string $name): ?BookmarkDescriptor
    {
        return $this->firstNamed($this->bookmarks, $name);
    }

    public function table(string $name): ?TableDescriptor
    {
        return $this->firstNamed($this->tables, $name);
    }

    public function frame(string $name): ?FrameDescriptor
    {
        return $this->firstNamed($this->frames, $name);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sections' => array_map(static fn (SectionDescriptor $item): array => $item->toArray(), $this->sections),
            'bookmarks' => array_map(static fn (BookmarkDescriptor $item): array => $item->toArray(), $this->bookmarks),
            'tables' => array_map(static fn (TableDescriptor $item): array => $item->toArray(), $this->tables),
            'frames' => array_map(static fn (FrameDescriptor $item): array => $item->toArray(), $this->frames),
            'diagnostics' => array_map(
                static fn (InspectionDiagnostic $item): array => $item->toArray(),
                $this->diagnostics
            ),
        ];
    }

    /**
     * @template T of object
     * @param list<T> $items
     * @return T|null
     */
    private function firstNamed(array $items, string $name): ?object
    {
        foreach ($items as $item) {
            if ($item->name() === $name) {
                return $item;
            }
        }

        return null;
    }
}
