<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable semantic classic-control description. */
final readonly class ControlDescriptor
{
    /**
     * @param list<SourceProvenance> $markerEvidence
     * @param list<string> $dependencyIds
     */
    public function __construct(
        private string $id,
        private string $kind,
        private string $representation,
        private string $supportState,
        private DataScopeDescriptor $scope,
        private array $markerEvidence,
        private array $dependencyIds = [],
        private ?DataScopeDescriptor $createdScope = null
    ) {
    }

    public function id(): string { return $this->id; }
    public function kind(): string { return $this->kind; }
    public function representation(): string { return $this->representation; }
    public function supportState(): string { return $this->supportState; }
    public function scope(): DataScopeDescriptor { return $this->scope; }
    /** @return list<SourceProvenance> */ public function markerEvidence(): array { return $this->markerEvidence; }
    /** @return list<string> */ public function dependencyIds(): array { return $this->dependencyIds; }
    public function createdScope(): ?DataScopeDescriptor { return $this->createdScope; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'representation' => $this->representation,
            'support_state' => $this->supportState,
            'scope' => $this->scope->toArray(),
            'marker_evidence' => array_map(
                static fn (SourceProvenance $item): array => $item->toArray(),
                $this->markerEvidence
            ),
            'dependency_ids' => $this->dependencyIds,
            'created_scope' => $this->createdScope?->toArray(),
        ];
    }
}
