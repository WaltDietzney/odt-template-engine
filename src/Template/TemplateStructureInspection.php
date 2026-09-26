<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable result of non-mutating template structure inspection. */
final readonly class TemplateStructureInspection
{
    /** @param list<TemplateExpressionDescriptor> $expressions @param list<TemplateStructureDiagnostic> $diagnostics */
    public function __construct(private array $expressions, private array $diagnostics) {}

    /** @return list<TemplateExpressionDescriptor> */ public function expressions(): array { return $this->expressions; }
    /** @return list<TemplateStructureDiagnostic> */ public function diagnostics(): array { return $this->diagnostics; }

    public function valid(): bool
    {
        return $this->unsafe() === [];
    }

    /** @return list<TemplateExpressionDescriptor|TemplateStructureDiagnostic> */
    public function repairable(): array
    {
        return array_values(array_filter(
            [...$this->expressions, ...$this->diagnostics],
            static fn (object $item): bool => $item instanceof TemplateExpressionDescriptor
                ? $item->classification() === 'REPAIRABLE'
                : $item->repairable()
        ));
    }

    /** @return list<TemplateExpressionDescriptor|TemplateStructureDiagnostic> */
    public function unsafe(): array
    {
        return array_values(array_filter(
            [...$this->expressions, ...$this->diagnostics],
            static fn (object $item): bool => $item instanceof TemplateExpressionDescriptor
                ? $item->classification() === 'UNSAFE'
                : $item->classification() === 'UNSAFE'
        ));
    }

    /** @return list<TemplateExpressionDescriptor> */
    public function expressionsByVariable(string $name): array
    {
        return array_values(array_filter($this->expressions, static fn (TemplateExpressionDescriptor $item): bool => $item->variableName() === $name));
    }

    /** @return list<TemplateExpressionDescriptor> */
    public function expressionsInScope(string $scope): array
    {
        return array_values(array_filter($this->expressions, static fn (TemplateExpressionDescriptor $item): bool => $item->scope() === $scope));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid(),
            'expressions' => array_map(static fn (TemplateExpressionDescriptor $item): array => $item->toArray(), $this->expressions),
            'diagnostics' => array_map(static fn (TemplateStructureDiagnostic $item): array => $item->toArray(), $this->diagnostics),
        ];
    }
}
