<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable logical data requirement deduplicated within one semantic data scope. */
final readonly class DependencyDescriptor
{
    /**
     * @param list<string> $evidenceIds
     */
    public function __construct(
        private string $id,
        private string $kind,
        private string $name,
        private DataScopeDescriptor $scope,
        private string $path,
        private array $evidenceIds
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function scope(): DataScopeDescriptor
    {
        return $this->scope;
    }

    public function path(): string
    {
        return $this->path;
    }

    /** @return list<string> */
    public function evidenceIds(): array
    {
        return $this->evidenceIds;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'name' => $this->name,
            'scope' => $this->scope->toArray(),
            'path' => $this->path,
            'evidence_ids' => $this->evidenceIds,
        ];
    }
}
