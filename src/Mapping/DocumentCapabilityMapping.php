<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Explicit application-source to a bounded document capability target. */
final readonly class DocumentCapabilityMapping implements MappingRule
{
    public function __construct(
        private ApplicationPath $source,
        private string $group,
        private string $target
    ) {
        if ($group === '' || $target === '') {
            throw new \InvalidArgumentException('Document capability target fields must not be empty.');
        }
    }

    public function source(): ApplicationPath
    {
        return $this->source;
    }

    public function group(): string
    {
        return $this->group;
    }

    public function target(): string
    {
        return $this->target;
    }
}
