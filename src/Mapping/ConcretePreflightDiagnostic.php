<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** One machine-readable concrete Phase-E preflight finding. */
final readonly class ConcretePreflightDiagnostic
{
    /** @param array<string, scalar|null> $context */
    public function __construct(
        private string $code,
        private string $message,
        private string $targetFamily,
        private string $targetIdentity,
        private ?string $sourcePath = null,
        private array $context = []
    ) {
    }

    public function code(): string { return $this->code; }
    public function message(): string { return $this->message; }
    public function targetFamily(): string { return $this->targetFamily; }
    public function targetIdentity(): string { return $this->targetIdentity; }
    public function sourcePath(): ?string { return $this->sourcePath; }
    /** @return array<string, scalar|null> */
    public function context(): array { return $this->context; }
}
