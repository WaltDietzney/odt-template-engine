<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * A machine-readable inspection diagnostic.
 */
final readonly class InspectionDiagnostic
{
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';

    public function __construct(
        private string $code,
        private string $severity,
        private string $message,
        private ?string $targetType = null,
        private ?string $targetName = null
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }

    public function severity(): string
    {
        return $this->severity;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function targetType(): ?string
    {
        return $this->targetType;
    }

    public function targetName(): ?string
    {
        return $this->targetName;
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'message' => $this->message,
            'target_type' => $this->targetType,
            'target_name' => $this->targetName,
        ];
    }
}
