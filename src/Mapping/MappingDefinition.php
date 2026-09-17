<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Immutable container of explicit mapping rules only. */
final readonly class MappingDefinition
{
    /**
     * @param list<DependencyMapping> $dependencies
     * @param list<NativeObjectActionMapping> $nativeObjectActions
     * @param list<DocumentCapabilityMapping> $documentCapabilities
     */
    public function __construct(
        private array $dependencies = [],
        private array $nativeObjectActions = [],
        private array $documentCapabilities = []
    ) {
        foreach ($dependencies as $mapping) {
            if (!$mapping instanceof DependencyMapping) {
                throw new \InvalidArgumentException('Dependency mappings must be explicit DependencyMapping values.');
            }
        }
        foreach ($nativeObjectActions as $mapping) {
            if (!$mapping instanceof NativeObjectActionMapping) {
                throw new \InvalidArgumentException('Native mappings must be NativeObjectActionMapping values.');
            }
        }
        foreach ($documentCapabilities as $mapping) {
            if (!$mapping instanceof DocumentCapabilityMapping) {
                throw new \InvalidArgumentException('Document mappings must be DocumentCapabilityMapping values.');
            }
        }
    }

    /** @return list<DependencyMapping> */
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    /** @return list<NativeObjectActionMapping> */
    public function nativeObjectActions(): array
    {
        return $this->nativeObjectActions;
    }

    /** @return list<DocumentCapabilityMapping> */
    public function documentCapabilities(): array
    {
        return $this->documentCapabilities;
    }
}
