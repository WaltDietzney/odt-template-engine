<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable semantic data scope used for logical dependency resolution. */
final readonly class DataScopeDescriptor
{
    public const ROOT = 'ROOT';

    public function __construct(
        private string $id,
        private string $kind,
        private ?string $parentId = null,
        private ?string $collectionDependencyId = null
    ) {
    }

    public static function root(): self
    {
        return new self('scope_root', self::ROOT);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function parentId(): ?string
    {
        return $this->parentId;
    }

    public function collectionDependencyId(): ?string
    {
        return $this->collectionDependencyId;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'parent_id' => $this->parentId,
            'collection_dependency_id' => $this->collectionDependencyId,
        ];
    }
}
