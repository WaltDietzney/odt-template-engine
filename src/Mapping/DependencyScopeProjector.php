<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\ControlDescriptor;
use OdtTemplateEngine\Template\DataScopeDescriptor;
use OdtTemplateEngine\Template\DependencyDescriptor;
use OdtTemplateEngine\Template\TemplateContract;

/** @internal Projects READY E2 dependency resolutions into their template scopes. */
final class DependencyScopeProjector
{
    /**
     * Project only a complete successful E2-C result. The projection contains
     * no application root data and performs no additional resolution.
     */
    public function project(
        TemplateContract $contract,
        ConcretePreflightResult $preflight
    ): DependencyScopeProjection {
        if (!$preflight->ready()) {
            throw new \InvalidArgumentException('Dependency scope projection requires a READY concrete preflight.');
        }

        $dependencyIndex = $this->indexDependencies($contract, $preflight);
        $scopeIndex = $this->indexScopes($contract);
        $this->validateScopeEdges($scopeIndex['scopes'], $dependencyIndex['dependencies']);

        return $this->buildScope(
            $scopeIndex['scopes'][DataScopeDescriptor::root()->id()],
            [],
            null,
            $dependencyIndex['dependenciesByScope'],
            $dependencyIndex['resolutions'],
            $scopeIndex['childrenByScope']
        );
    }

    /**
     * @return array{
     *   dependencies:array<string,DependencyDescriptor>,
     *   resolutions:array<string,DependencyMappingResolution>,
     *   dependenciesByScope:array<string,list<string>>
     * }
     */
    private function indexDependencies(
        TemplateContract $contract,
        ConcretePreflightResult $preflight
    ): array {
        $dependencies = [];
        $resolutions = [];
        $dependenciesByScope = [];

        foreach ($contract->dependencies() as $dependency) {
            if (isset($dependencies[$dependency->id()])) {
                throw new \LogicException('TemplateContract contains duplicate dependency identities.');
            }
            $dependencies[$dependency->id()] = $dependency;
        }

        foreach ($preflight->mappingResolution()->dependencies() as $resolution) {
            $id = $resolution->target()->id();
            $target = $dependencies[$id] ?? null;
            if (!$target instanceof DependencyDescriptor || $target->toArray() !== $resolution->target()->toArray()) {
                throw new \LogicException('Mapping resolution target does not match the supplied TemplateContract.');
            }
            if (isset($resolutions[$id]) || $resolution->status() !== DependencyMappingResolution::RESOLVED) {
                throw new \LogicException('READY preflight contains a duplicate or unresolved dependency resolution.');
            }
            $resolutions[$id] = $resolution;
            $dependenciesByScope[$target->scope()->id()][] = $id;
        }

        if (count($dependencies) !== count($resolutions)) {
            throw new \LogicException('READY preflight does not resolve every TemplateContract dependency.');
        }

        $dependencyOperations = [];
        foreach ($preflight->operations() as $operation) {
            $operationResolution = $operation->resolution();
            if ($operation->targetFamily() !== 'dependency'
                || !$operationResolution instanceof DependencyMappingResolution
            ) {
                continue;
            }
            $id = $operationResolution->target()->id();
            if (isset($dependencyOperations[$id])) {
                throw new \LogicException('READY preflight contains duplicate dependency operations.');
            }
            $dependencyOperations[$id] = $operation;
        }
        foreach ($resolutions as $id => $resolution) {
            $operation = $dependencyOperations[$id] ?? null;
            if ($operation === null
                || $operation->status() !== ConcretePreflightOperation::READY
                || $operation->resolution() !== $resolution
            ) {
                throw new \LogicException('Dependency projection is not backed by its READY preflight operation.');
            }
        }
        if (count($dependencyOperations) !== count($resolutions)) {
            throw new \LogicException('READY preflight contains dependency operations outside the supplied contract.');
        }

        return [
            'dependencies' => $dependencies,
            'resolutions' => $resolutions,
            'dependenciesByScope' => $dependenciesByScope,
        ];
    }

    /**
     * @return array{
     *   scopes:array<string,DataScopeDescriptor>,
     *   childrenByScope:array<string,list<DataScopeDescriptor>>
     * }
     */
    private function indexScopes(TemplateContract $contract): array
    {
        $root = DataScopeDescriptor::root();
        $scopes = [$root->id() => $root];
        $childrenByScope = [];

        foreach ($contract->dependencies() as $dependency) {
            $this->addScope($scopes, $dependency->scope());
        }
        foreach ($contract->controls() as $control) {
            if (!$control instanceof ControlDescriptor) {
                continue;
            }
            $this->addScope($scopes, $control->scope());
            if ($control->createdScope() !== null) {
                $this->addScope($scopes, $control->createdScope());
            }
        }

        foreach ($scopes as $scope) {
            if ($scope->kind() === DataScopeDescriptor::ROOT) {
                if ($scope->id() !== DataScopeDescriptor::root()->id() || $scope->parentId() !== null) {
                    throw new \LogicException('ROOT scope cannot have a parent.');
                }
                continue;
            }
            if ($scope->kind() !== DataScopeDescriptor::COLLECTION_ITEM) {
                throw new \LogicException('Template scope kind is not supported by the E2-to-E3 projection.');
            }

            $parentId = $scope->parentId();
            if ($parentId === null || !isset($scopes[$parentId])) {
                throw new \LogicException('Template scope has no represented parent scope.');
            }
            $childrenByScope[$parentId][] = $scope;
        }

        return ['scopes' => $scopes, 'childrenByScope' => $childrenByScope];
    }

    /** @param array<string,DataScopeDescriptor> $scopes */
    private function addScope(array &$scopes, DataScopeDescriptor $scope): void
    {
        $existing = $scopes[$scope->id()] ?? null;
        if ($existing !== null && $existing->toArray() !== $scope->toArray()) {
            throw new \LogicException('TemplateContract contains conflicting descriptors for one scope identity.');
        }
        $scopes[$scope->id()] = $scope;
    }

    /**
     * @param array<string,DataScopeDescriptor> $scopes
     * @param array<string,DependencyDescriptor> $dependencies
     */
    private function validateScopeEdges(array $scopes, array $dependencies): void
    {
        $collectionScopes = [];
        foreach ($scopes as $scope) {
            if ($scope->kind() === DataScopeDescriptor::ROOT) {
                continue;
            }
            $collectionId = $scope->collectionDependencyId();
            $collection = $collectionId === null ? null : ($dependencies[$collectionId] ?? null);
            if (!$collection instanceof DependencyDescriptor
                || $collection->kind() !== 'COLLECTION'
                || $collection->scope()->id() !== $scope->parentId()
            ) {
                throw new \LogicException('Template item scope does not match its collection dependency owner.');
            }
            if (isset($collectionScopes[$collectionId]) && $collectionScopes[$collectionId] !== $scope->id()) {
                throw new \LogicException('One collection dependency identifies conflicting item scopes.');
            }
            $collectionScopes[$collectionId] = $scope->id();
        }
    }

    /**
     * @param list<int> $lineage
     * @param array<string,list<string>> $dependenciesByScope
     * @param array<string,DependencyMappingResolution> $resolutions
     * @param array<string,list<DataScopeDescriptor>> $childrenByScope
     */
    private function buildScope(
        DataScopeDescriptor $scope,
        array $lineage,
        ?int $itemIndex,
        array $dependenciesByScope,
        array $resolutions,
        array $childrenByScope
    ): DependencyScopeProjection {
        $values = [];
        foreach ($dependenciesByScope[$scope->id()] ?? [] as $dependencyId) {
            $resolution = $resolutions[$dependencyId];
            $localData = $this->atLineage($resolution->dataResolution(), $lineage);
            $values[$dependencyId] = new ProjectedDependencyValue(
                $resolution->target(),
                (string) $resolution->provenance(),
                $localData->status(),
                $resolution->target()->kind() === 'COLLECTION' ? null : $localData->value()
            );
        }

        $collectionItems = [];
        foreach ($childrenByScope[$scope->id()] ?? [] as $childScope) {
            $collectionId = (string) $childScope->collectionDependencyId();
            $owner = $resolutions[$collectionId] ?? null;
            if (!$owner instanceof DependencyMappingResolution) {
                throw new \LogicException('Collection item scope has no resolved collection owner.');
            }

            $ownerData = $this->atLineage($owner->dataResolution(), $lineage);
            $expectedIndexes = $this->collectionIndexes($ownerData);
            $this->assertDescendantLineage(
                $childScope,
                $lineage,
                $expectedIndexes,
                $dependenciesByScope,
                $resolutions,
                $childrenByScope
            );

            $collectionItems[$collectionId] = [];
            foreach ($ownerData->items() as $item) {
                $index = $item->itemIndex();
                if ($index === null) {
                    throw new \LogicException('Collection lineage item has no item index.');
                }
                $collectionItems[$collectionId][] = $this->buildScope(
                    $childScope,
                    [...$lineage, $index],
                    $index,
                    $dependenciesByScope,
                    $resolutions,
                    $childrenByScope
                );
            }
        }

        return new DependencyScopeProjection($scope, $itemIndex, $values, $collectionItems);
    }

    /**
     * @param list<int> $parentLineage
     * @param list<int> $expectedIndexes
     * @param array<string,list<string>> $dependenciesByScope
     * @param array<string,DependencyMappingResolution> $resolutions
     * @param array<string,list<DataScopeDescriptor>> $childrenByScope
     */
    private function assertDescendantLineage(
        DataScopeDescriptor $childScope,
        array $parentLineage,
        array $expectedIndexes,
        array $dependenciesByScope,
        array $resolutions,
        array $childrenByScope
    ): void {
        foreach ($this->descendantDependencyIds($childScope->id(), $dependenciesByScope, $childrenByScope) as $dependencyId) {
            $candidate = $this->atLineage(
                $resolutions[$dependencyId]->dataResolution(),
                $parentLineage
            );
            if ($this->indexes($candidate->items()) !== $expectedIndexes) {
                throw new \LogicException(sprintf(
                    'Dependency %s has collection lineage inconsistent with template scope %s.',
                    $dependencyId,
                    $childScope->id()
                ));
            }
        }
    }

    /**
     * @param array<string,list<string>> $dependenciesByScope
     * @param array<string,list<DataScopeDescriptor>> $childrenByScope
     * @return list<string>
     */
    private function descendantDependencyIds(
        string $scopeId,
        array $dependenciesByScope,
        array $childrenByScope
    ): array {
        $ids = $dependenciesByScope[$scopeId] ?? [];
        foreach ($childrenByScope[$scopeId] ?? [] as $child) {
            array_push($ids, ...$this->descendantDependencyIds($child->id(), $dependenciesByScope, $childrenByScope));
        }
        return $ids;
    }

    /** @param list<ApplicationDataResolution> $items @return list<int> */
    private function indexes(array $items): array
    {
        $indexes = [];
        foreach ($items as $position => $item) {
            if ($item->itemIndex() !== $position) {
                throw new \LogicException('Application collection lineage indices are not an ordered local list.');
            }
            $indexes[] = $position;
        }
        return $indexes;
    }

    /** @return list<int> */
    private function collectionIndexes(ApplicationDataResolution $data): array
    {
        if ($data->status() === ApplicationDataResolution::EMPTY_COLLECTION) {
            if ($data->items() !== []) {
                throw new \LogicException('Empty collection resolution unexpectedly contains items.');
            }
            return [];
        }
        if ($data->status() !== ApplicationDataResolution::PRESENT) {
            throw new \LogicException('READY preflight contains a collection with an invalid data state.');
        }
        if ($data->items() === []) {
            throw new \LogicException('Present collection resolution contains no lineage items.');
        }
        return $this->indexes($data->items());
    }

    /** @param list<int> $lineage */
    private function atLineage(?ApplicationDataResolution $data, array $lineage): ApplicationDataResolution
    {
        if ($data === null) {
            throw new \LogicException('Resolved dependency has no application-data resolution.');
        }
        foreach ($lineage as $index) {
            $matches = array_values(array_filter(
                $data->items(),
                static fn (ApplicationDataResolution $item): bool => $item->itemIndex() === $index
            ));
            if (count($matches) !== 1) {
                throw new \LogicException('Dependency resolution cannot be aligned to the established collection lineage.');
            }
            $data = $matches[0];
        }
        return $data;
    }
}
