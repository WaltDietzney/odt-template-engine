<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\DependencyDescriptor;

final readonly class ProjectedDependencyTarget
{
    public function __construct(
        private DependencyDescriptor $dependency,
        private Applicability $applicability,
        private ?string $reason = null
    ) {
    }

    public function dependency(): DependencyDescriptor
    {
        return $this->dependency;
    }

    public function supported(): bool
    {
        return true;
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
