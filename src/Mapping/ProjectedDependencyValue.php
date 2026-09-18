<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\DependencyDescriptor;

/** @internal One resolved dependency viewed in a concrete template scope. */
final readonly class ProjectedDependencyValue
{
    public function __construct(
        private DependencyDescriptor $target,
        private string $provenance,
        private string $status,
        private mixed $value
    ) {
        if (!in_array($provenance, [
            DependencyMappingResolution::EXPLICIT,
            DependencyMappingResolution::SCOPED_SAME_NAME,
        ], true)) {
            throw new \InvalidArgumentException('Projected dependency provenance is invalid.');
        }
        if ($target->kind() === 'COLLECTION' && $value !== null) {
            throw new \InvalidArgumentException('Collection records must be represented by projected template items.');
        }
    }

    public function target(): DependencyDescriptor { return $this->target; }
    public function provenance(): string { return $this->provenance; }
    public function status(): string { return $this->status; }

    /**
     * The concrete template dependency value. Collection records are represented
     * by their template-scope item projections, not application record keys.
     */
    public function value(): mixed
    {
        return $this->value;
    }
}
