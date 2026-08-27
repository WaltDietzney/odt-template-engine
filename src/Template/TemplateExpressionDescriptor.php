<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable description of one logically projected template expression. */
final readonly class TemplateExpressionDescriptor
{
    /** @param list<string> $styleNames @param list<string> $bookmarkNames @param list<string> $diagnostics */
    public function __construct(
        private string $rawText,
        private string $kind,
        private ?string $variableName,
        private ?string $filterName,
        private ?string $filterOption,
        private string $scope,
        private int $fragmentCount,
        private array $styleNames,
        private array $bookmarkNames,
        private string $classification,
        private string $physicalNormalization,
        private array $diagnostics = [],
    ) {}

    public function rawText(): string { return $this->rawText; }
    public function kind(): string { return $this->kind; }
    public function variableName(): ?string { return $this->variableName; }
    public function filterName(): ?string { return $this->filterName; }
    public function filterOption(): ?string { return $this->filterOption; }
    public function scope(): string { return $this->scope; }
    public function fragmentCount(): int { return $this->fragmentCount; }
    public function isSplit(): bool { return $this->fragmentCount > 1; }
    /** @return list<string> */ public function styleNames(): array { return $this->styleNames; }
    /** @return list<string> */ public function bookmarkNames(): array { return $this->bookmarkNames; }
    public function classification(): string { return $this->classification; }
    public function physicalNormalization(): string { return $this->physicalNormalization; }
    /** @return list<string> */ public function diagnostics(): array { return $this->diagnostics; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'raw_text' => $this->rawText,
            'kind' => $this->kind,
            'variable_name' => $this->variableName,
            'filter_name' => $this->filterName,
            'filter_option' => $this->filterOption,
            'scope' => $this->scope,
            'fragment_count' => $this->fragmentCount,
            'split' => $this->isSplit(),
            'style_names' => $this->styleNames,
            'bookmark_names' => $this->bookmarkNames,
            'classification' => $this->classification,
            'physical_normalization' => $this->physicalNormalization,
            'diagnostics' => $this->diagnostics,
        ];
    }
}
