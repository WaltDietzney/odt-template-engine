<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable diagnostic attached to the unified template contract. */
final readonly class TemplateContractDiagnostic
{
    public function __construct(
        private string $code,
        private string $severity,
        private string $message,
        private ?string $subjectId = null,
        private ?SourceProvenance $provenance = null
    ) {
    }

    public function code(): string { return $this->code; }
    public function severity(): string { return $this->severity; }
    public function message(): string { return $this->message; }
    public function subjectId(): ?string { return $this->subjectId; }
    public function provenance(): ?SourceProvenance { return $this->provenance; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'message' => $this->message,
            'subject_id' => $this->subjectId,
            'provenance' => $this->provenance?->toArray(),
        ];
    }
}
