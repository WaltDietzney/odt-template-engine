<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/**
 * Immutable authored binding site.
 *
 * Slice 1 records source binding evidence only. Scoped logical dependencies
 * are introduced by later Phase-B slices.
 */
final readonly class BindingDescriptor
{
    public function __construct(
        private string $kind,
        private string $rawText,
        private ?string $variableName,
        private ?string $filterName,
        private ?string $filterOption,
        private string $supportState,
        private SourceProvenance $provenance
    ) {
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function rawText(): string
    {
        return $this->rawText;
    }

    public function variableName(): ?string
    {
        return $this->variableName;
    }

    public function supportState(): string
    {
        return $this->supportState;
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
            'raw_text' => $this->rawText,
            'variable_name' => $this->variableName,
            'filter_name' => $this->filterName,
            'filter_option' => $this->filterOption,
            'support_state' => $this->supportState,
            'provenance' => $this->provenance->toArray(),
        ];
    }
}
