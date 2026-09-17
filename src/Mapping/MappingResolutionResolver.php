<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\DataScopeDescriptor;
use OdtTemplateEngine\Template\DependencyDescriptor;
use OdtTemplateEngine\Template\TemplateContract;

/** Resolves explicit and established-scope same-name sources without document mutation. */
final class MappingResolutionResolver
{
    public function __construct(private ?ApplicationDataResolver $applicationDataResolver = null)
    {
        $this->applicationDataResolver ??= new ApplicationDataResolver();
    }

    /**
     * @param array<mixed> $data Canonical application data tree.
     */
    public function resolve(
        MappingDefinition $definition,
        TemplateContract $contract,
        array $data
    ): MappingResolution {
        $catalog = EngineCapabilityCatalog::phaseE1();
        $projection = (new TemplateCapabilityProjector())->project($contract, $catalog);
        $validation = (new StaticMappingValidator())->validate($definition, $contract, $projection, $catalog);
        if (!$validation->valid()) {
            throw new MappingResolutionException($validation);
        }

        $explicitByTarget = [];
        foreach ($definition->dependencies() as $mapping) {
            $explicitByTarget[$mapping->dependencyPath()] = $mapping;
        }

        $dependenciesById = [];
        foreach ($contract->dependencies() as $dependency) {
            $dependenciesById[$dependency->id()] = $dependency;
        }

        $dependencyResolutions = [];
        foreach ($contract->dependencies() as $dependency) {
            $explicit = $explicitByTarget[$dependency->path()] ?? null;
            if ($explicit instanceof DependencyMapping) {
                $dependencyResolutions[] = $this->resolvedDependency(
                    $dependency,
                    $explicit->source(),
                    DependencyMappingResolution::EXPLICIT,
                    $data
                );
                continue;
            }

            $source = $this->sameNameSource($dependency, $explicitByTarget, $dependenciesById);
            if ($source === null) {
                $dependencyResolutions[] = new DependencyMappingResolution(
                    $dependency,
                    DependencyMappingResolution::UNRESOLVED
                );
                continue;
            }

            $dependencyResolutions[] = $this->resolvedDependency(
                $dependency,
                $source,
                DependencyMappingResolution::SCOPED_SAME_NAME,
                $data
            );
        }

        $nativeResolutions = [];
        foreach ($definition->nativeObjectActions() as $mapping) {
            $nativeResolutions[] = new NativeObjectActionResolution(
                $mapping,
                $this->dataResolver()->resolve($mapping->source(), $data)
            );
        }

        $documentResolutions = [];
        foreach ($definition->documentCapabilities() as $mapping) {
            $documentResolutions[] = new DocumentCapabilityResolution(
                $mapping,
                $this->dataResolver()->resolve($mapping->source(), $data)
            );
        }

        return new MappingResolution($dependencyResolutions, $nativeResolutions, $documentResolutions);
    }

    /**
     * @param array<string, DependencyMapping> $explicitByTarget
     * @param array<string, DependencyDescriptor> $dependenciesById
     */
    private function sameNameSource(
        DependencyDescriptor $dependency,
        array $explicitByTarget,
        array $dependenciesById
    ): ?ApplicationPath {
        // Collection boundaries are never established by convention.
        if ($dependency->kind() !== 'VALUE') {
            return null;
        }

        $scope = $dependency->scope();
        if ($scope->kind() === DataScopeDescriptor::ROOT) {
            return $this->singleValuePath($dependency->name());
        }

        $applicationScopePaths = [];
        $seenScopeIds = [];
        while ($scope->kind() === DataScopeDescriptor::COLLECTION_ITEM) {
            if (isset($seenScopeIds[$scope->id()])) {
                return null;
            }
            $seenScopeIds[$scope->id()] = true;

            $collectionDependencyId = $scope->collectionDependencyId();
            if ($collectionDependencyId === null) {
                return null;
            }
            $collectionDependency = $dependenciesById[$collectionDependencyId] ?? null;
            if (!$collectionDependency instanceof DependencyDescriptor
                || $collectionDependency->kind() !== 'COLLECTION'
            ) {
                return null;
            }

            $mapping = $explicitByTarget[$collectionDependency->path()] ?? null;
            if (!$mapping instanceof DependencyMapping
                || $mapping->source()->terminalKind() !== ApplicationPathSegment::COLLECTION
            ) {
                return null;
            }

            $applicationScopePaths[] = $mapping->source();
            // The collection dependency belongs to the parent scope of this item scope.
            $scope = $collectionDependency->scope();
        }

        if ($scope->kind() !== DataScopeDescriptor::ROOT || $applicationScopePaths === []) {
            return null;
        }

        // Traversal starts at the target's immediate item scope, so the first pair
        // collected is the deepest established application collection scope.
        return $this->appendValueName($applicationScopePaths[0], $dependency->name());
    }

    private function singleValuePath(string $name): ?ApplicationPath
    {
        try {
            $path = ApplicationPath::parse($name);
        } catch (\InvalidArgumentException) {
            return null;
        }

        if (count($path->segments()) !== 1 || $path->terminalKind() !== ApplicationPathSegment::VALUE) {
            return null;
        }

        return $path;
    }

    private function appendValueName(ApplicationPath $collectionScope, string $name): ?ApplicationPath
    {
        $localPath = $this->singleValuePath($name);
        if ($localPath === null) {
            return null;
        }

        try {
            return ApplicationPath::parse($collectionScope->canonical() . '.' . $localPath->canonical());
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /** @param array<mixed> $data */
    private function resolvedDependency(
        DependencyDescriptor $dependency,
        ApplicationPath $source,
        string $provenance,
        array $data
    ): DependencyMappingResolution {
        return new DependencyMappingResolution(
            $dependency,
            DependencyMappingResolution::RESOLVED,
            $source,
            $provenance,
            $this->dataResolver()->resolve($source, $data)
        );
    }

    private function dataResolver(): ApplicationDataResolver
    {
        return $this->applicationDataResolver ??= new ApplicationDataResolver();
    }
}
