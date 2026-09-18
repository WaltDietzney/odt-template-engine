<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Read-only readiness projection for one resolved Phase-E operation. */
final readonly class ConcretePreflightOperation
{
    public const READY = 'READY';
    public const ERROR = 'ERROR';

    /**
     * @param list<ConcretePreflightDiagnostic> $diagnostics
     */
    public function __construct(
        private string $targetFamily,
        private string $targetIdentity,
        private DependencyMappingResolution|NativeObjectActionResolution|DocumentCapabilityResolution $resolution,
        private ?string $capabilityId,
        private ?string $payloadKind,
        private ?string $applicability,
        private string $status,
        private array $diagnostics
    ) {
        if (!in_array($status, [self::READY, self::ERROR], true)) {
            throw new \InvalidArgumentException('Concrete preflight operation is invalid.');
        }
    }

    public function targetFamily(): string { return $this->targetFamily; }
    public function targetIdentity(): string { return $this->targetIdentity; }
    public function resolution(): DependencyMappingResolution|NativeObjectActionResolution|DocumentCapabilityResolution { return $this->resolution; }
    public function capabilityId(): ?string { return $this->capabilityId; }
    public function payloadKind(): ?string { return $this->payloadKind; }
    public function applicability(): ?string { return $this->applicability; }
    public function status(): string { return $this->status; }
    /** @return list<ConcretePreflightDiagnostic> */
    public function diagnostics(): array { return $this->diagnostics; }
}
