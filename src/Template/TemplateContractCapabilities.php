<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable capability-readiness projection for a template contract. */
final readonly class TemplateContractCapabilities
{
    public const READY = 'READY';
    public const LIMITED = 'LIMITED';
    public const BLOCKED = 'BLOCKED';
    public const NOT_APPLICABLE = 'NOT_APPLICABLE';

    /** @param array<string, string> $readiness */
    public function __construct(private array $readiness)
    {
    }

    public function readiness(string $capability): ?string
    {
        return $this->readiness[$capability] ?? null;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->readiness;
    }
}
