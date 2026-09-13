<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable semantic contract derived from the original authored ODT source. */
final readonly class TemplateContract
{
    public const CONTRACT_VERSION = 1;

    /**
     * @param list<BindingDescriptor> $bindings
     * @param list<ControlDescriptor> $controls
     * @param list<NativeObjectDescriptor> $nativeObjects
     * @param list<DependencyDescriptor> $dependencies
     * @param list<TemplateContractDiagnostic> $diagnostics
     */
    public function __construct(
        private array $bindings,
        private array $controls,
        private array $nativeObjects,
        private array $dependencies,
        private array $diagnostics,
        private TemplateContractCoverage $coverage,
        private TemplateContractCapabilities $capabilities
    ) {
    }

    /** @return list<BindingDescriptor> */
    public function bindings(): array
    {
        return $this->bindings;
    }

    /** @return list<object> */
    public function controls(): array
    {
        return $this->controls;
    }

    /** @return list<NativeObjectDescriptor> */
    public function nativeObjects(): array
    {
        return $this->nativeObjects;
    }

    /** @return list<DependencyDescriptor> */
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    /** @return list<object> */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    public function coverage(): TemplateContractCoverage
    {
        return $this->coverage;
    }

    public function capabilities(): TemplateContractCapabilities
    {
        return $this->capabilities;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'coverage' => $this->coverage->toArray(),
            'bindings' => array_map(
                static fn (BindingDescriptor $item): array => $item->toArray(),
                $this->bindings
            ),
            'controls' => array_map(
                static fn (ControlDescriptor $item): array => $item->toArray(),
                $this->controls
            ),
            'native_objects' => array_map(
                static fn (NativeObjectDescriptor $item): array => $item->toArray(),
                $this->nativeObjects
            ),
            'dependencies' => array_map(
                static fn (DependencyDescriptor $item): array => $item->toArray(),
                $this->dependencies
            ),
            'capabilities' => $this->capabilities->toArray(),
            'diagnostics' => array_map(
                static fn (TemplateContractDiagnostic $item): array => $item->toArray(),
                $this->diagnostics
            ),
        ];
    }
}
