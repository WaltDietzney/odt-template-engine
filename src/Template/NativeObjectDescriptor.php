<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable source-oriented native ODF object evidence. */
final readonly class NativeObjectDescriptor
{
    /**
     * @param list<string> $ownerIds
     */
    public function __construct(
        private string $kind,
        private ?string $name,
        private SourceProvenance $provenance,
        private ?string $id = null,
        private array $ownerIds = []
    ) {
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function provenance(): SourceProvenance
    {
        return $this->provenance;
    }

    public function id(): string
    {
        return $this->id ?? $this->provenance->evidenceId();
    }

    /** @return list<string> */
    public function ownerIds(): array
    {
        return $this->ownerIds;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'kind' => $this->kind,
            'name' => $this->name,
            'owner_ids' => $this->ownerIds,
            'provenance' => $this->provenance->toArray(),
        ];
    }
}
