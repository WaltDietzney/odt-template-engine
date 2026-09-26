<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

use OdtTemplateEngine\Template\DependencyDescriptor;

/** Read-only application-source resolution for one TemplateContract dependency. */
final readonly class DependencyMappingResolution
{
    public const RESOLVED = 'RESOLVED';
    public const UNRESOLVED = 'UNRESOLVED';
    public const EXPLICIT = 'EXPLICIT';
    public const SCOPED_SAME_NAME = 'SCOPED_SAME_NAME';

    public function __construct(
        private DependencyDescriptor $target,
        private string $status,
        private ?ApplicationPath $source = null,
        private ?string $provenance = null,
        private ?ApplicationDataResolution $dataResolution = null
    ) {
        $resolved = $status === self::RESOLVED;
        if ((!$resolved && $status !== self::UNRESOLVED)
            || ($resolved && ($source === null || $provenance === null || $dataResolution === null))
            || (!$resolved && ($source !== null || $provenance !== null || $dataResolution !== null))
            || ($provenance !== null && !in_array($provenance, [self::EXPLICIT, self::SCOPED_SAME_NAME], true))
        ) {
            throw new \InvalidArgumentException('Dependency mapping resolution is inconsistent.');
        }
    }

    public function target(): DependencyDescriptor
    {
        return $this->target;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function source(): ?ApplicationPath
    {
        return $this->source;
    }

    /** EXPLICIT or SCOPED_SAME_NAME for resolved targets; null when unresolved. */
    public function provenance(): ?string
    {
        return $this->provenance;
    }

    public function dataResolution(): ?ApplicationDataResolution
    {
        return $this->dataResolution;
    }
}
