<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable semantic data scope used for logical dependency resolution. */
final readonly class DataScopeDescriptor
{
    public const ROOT = 'ROOT';
    public const COLLECTION_ITEM = 'COLLECTION_ITEM';

    public function __construct(
        private string $id,
        private string $kind,
        private ?string $parentId = null,
        private ?string $collectionDependencyId = null,
        private string $pathPrefix = ''
    ) {
    }

    public static function root(): self
    {
        return new self('scope_root', self::ROOT);
    }

    public static function collectionItem(
        self $parent,
        string $collectionDependencyId,
        string $collectionPath
    ): self {
        $seed = implode('|', [$parent->id(), $collectionDependencyId, $collectionPath]);

        return new self(
            'scope_' . substr(hash('sha256', $seed), 0, 16),
            self::COLLECTION_ITEM,
            $parent->id(),
            $collectionDependencyId,
            $collectionPath
        );
    }

    public function id(): string { return $this->id; }
    public function kind(): string { return $this->kind; }
    public function parentId(): ?string { return $this->parentId; }
    public function collectionDependencyId(): ?string { return $this->collectionDependencyId; }
    public function pathPrefix(): string { return $this->pathPrefix; }

    public function dependencyPath(string $name, bool $collection = false): string
    {
        $suffix = $collection ? '[]' : '';
        if ($this->pathPrefix === '') {
            return $name . $suffix;
        }

        return $this->pathPrefix . '.' . $name . $suffix;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'parent_id' => $this->parentId,
            'collection_dependency_id' => $this->collectionDependencyId,
            'path_prefix' => $this->pathPrefix,
        ];
    }
}
