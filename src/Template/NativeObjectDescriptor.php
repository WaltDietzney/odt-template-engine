<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable source-oriented native ODF object evidence. */
final readonly class NativeObjectDescriptor
{
    public function __construct(
        private string $kind,
        private ?string $name,
        private SourceProvenance $provenance
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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'name' => $this->name,
            'provenance' => $this->provenance->toArray(),
        ];
    }
}
