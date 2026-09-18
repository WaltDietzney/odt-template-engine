<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

final readonly class ProjectedNativeAction
{
    public function __construct(
        private NativeActionCapability $capability,
        private Applicability $applicability,
        private ?string $reason = null
    ) {
    }

    public function capability(): NativeActionCapability
    {
        return $this->capability;
    }

    public function applicability(): Applicability
    {
        return $this->applicability;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
