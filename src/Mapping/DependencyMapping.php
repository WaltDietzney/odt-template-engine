<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Explicit application-source to semantic TemplateContract dependency path. */
final readonly class DependencyMapping implements MappingRule
{
    public function __construct(
        private ApplicationPath $source,
        private string $dependencyPath
    ) {
        if ($dependencyPath === '') {
            throw new \InvalidArgumentException('Dependency target path must not be empty.');
        }
    }

    public function source(): ApplicationPath
    {
        return $this->source;
    }

    public function dependencyPath(): string
    {
        return $this->dependencyPath;
    }
}
