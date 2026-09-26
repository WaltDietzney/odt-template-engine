<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\DataScopeDescriptor;

/** @internal Immutable read-only view of resolved dependencies in one template scope. */
final readonly class DependencyScopeProjection
{
    /**
     * @param array<string, ProjectedDependencyValue> $dependencies Keyed by dependency ID.
     * @param array<string, list<DependencyScopeProjection>> $collectionItems Keyed by collection dependency ID.
     */
    public function __construct(
        private DataScopeDescriptor $scope,
        private ?int $itemIndex,
        private array $dependencies,
        private array $collectionItems
    ) {
        foreach ($dependencies as $dependency) {
            if (!$dependency instanceof ProjectedDependencyValue) {
                throw new \InvalidArgumentException('Projected dependencies must be typed values.');
            }
        }
        foreach ($collectionItems as $items) {
            if (!is_array($items) || !array_is_list($items)) {
                throw new \InvalidArgumentException('Projected collection items must be ordered lists.');
            }
            foreach ($items as $item) {
                if (!$item instanceof self) {
                    throw new \InvalidArgumentException('Projected collection items must be scope projections.');
                }
            }
        }
    }

    public function scope(): DataScopeDescriptor { return $this->scope; }
    public function itemIndex(): ?int { return $this->itemIndex; }

    /** @return array<string, ProjectedDependencyValue> Keyed by dependency ID. */
    public function dependencies(): array { return $this->dependencies; }

    public function dependency(string $dependencyId): ?ProjectedDependencyValue
    {
        return $this->dependencies[$dependencyId] ?? null;
    }

    /** @return list<DependencyScopeProjection> */
    public function collectionItems(string $collectionDependencyId): array
    {
        return $this->collectionItems[$collectionDependencyId] ?? [];
    }
}
