<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Read-only, template-specific view of engine capabilities. */
final readonly class TemplateCapabilityProjection
{
    /**
     * @param list<ProjectedDependencyTarget> $dependencyTargets
     * @param list<ProjectedNativeObjectTarget> $nativeObjectTargets
     * @param list<ProjectedDocumentCapability> $documentCapabilities
     */
    public function __construct(
        private array $dependencyTargets,
        private array $nativeObjectTargets,
        private array $documentCapabilities
    ) {
    }

    /** @return list<ProjectedDependencyTarget> */
    public function dependencyTargets(): array
    {
        return $this->dependencyTargets;
    }

    /** @return list<ProjectedNativeObjectTarget> */
    public function nativeObjectTargets(): array
    {
        return $this->nativeObjectTargets;
    }

    /** @return list<ProjectedDocumentCapability> */
    public function documentCapabilities(): array
    {
        return $this->documentCapabilities;
    }

    public function dependency(string $path): ?ProjectedDependencyTarget
    {
        foreach ($this->dependencyTargets as $target) {
            if ($target->dependency()->path() === $path) {
                return $target;
            }
        }

        return null;
    }

    public function nativeObject(string $kind, string $name): ?ProjectedNativeObjectTarget
    {
        foreach ($this->nativeObjectTargets as $target) {
            $object = $target->nativeObject();
            if ($object->kind() === $kind && $object->name() === $name) {
                return $target;
            }
        }

        return null;
    }

    public function documentCapability(string $group, string $target): ?ProjectedDocumentCapability
    {
        foreach ($this->documentCapabilities as $capability) {
            if ($capability->group() === $group && $capability->target() === $target) {
                return $capability;
            }
        }

        return null;
    }
}
