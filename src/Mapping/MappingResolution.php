<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Read-only resolution result across the three explicit mapping target families. */
final readonly class MappingResolution
{
    /**
     * @param list<DependencyMappingResolution> $dependencies
     * @param list<NativeObjectActionResolution> $nativeObjectActions
     * @param list<DocumentCapabilityResolution> $documentCapabilities
     */
    public function __construct(
        private array $dependencies,
        private array $nativeObjectActions,
        private array $documentCapabilities
    ) {
        foreach ($dependencies as $resolution) {
            if (!$resolution instanceof DependencyMappingResolution) {
                throw new \InvalidArgumentException('Dependency resolutions must be typed values.');
            }
        }
        foreach ($nativeObjectActions as $resolution) {
            if (!$resolution instanceof NativeObjectActionResolution) {
                throw new \InvalidArgumentException('Native action resolutions must be typed values.');
            }
        }
        foreach ($documentCapabilities as $resolution) {
            if (!$resolution instanceof DocumentCapabilityResolution) {
                throw new \InvalidArgumentException('Document capability resolutions must be typed values.');
            }
        }
    }

    /** @return list<DependencyMappingResolution> */
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    /** @return list<NativeObjectActionResolution> */
    public function nativeObjectActions(): array
    {
        return $this->nativeObjectActions;
    }

    /** @return list<DocumentCapabilityResolution> */
    public function documentCapabilities(): array
    {
        return $this->documentCapabilities;
    }
}
