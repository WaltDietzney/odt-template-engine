<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable, machine-readable template topology diagnostic. */
final readonly class TemplateStructureDiagnostic
{
    public function __construct(
        private string $code,
        private string $severity,
        private string $message,
        private string $classification,
        private bool $repairable,
        private ?string $expression = null,
        private ?string $scope = null,
    ) {}

    public function code(): string { return $this->code; }
    public function severity(): string { return $this->severity; }
    public function message(): string { return $this->message; }
    public function classification(): string { return $this->classification; }
    public function repairable(): bool { return $this->repairable; }
    public function expression(): ?string { return $this->expression; }
    public function scope(): ?string { return $this->scope; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'message' => $this->message,
            'classification' => $this->classification,
            'repairable' => $this->repairable,
            'expression' => $this->expression,
            'scope' => $this->scope,
        ];
    }
}
