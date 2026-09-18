<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Source binding for an explicit native-object action mapping. */
final readonly class NativeObjectActionResolution
{
    public const RESOLVED = 'RESOLVED';
    public const EXPLICIT = 'EXPLICIT';

    public function __construct(
        private NativeObjectActionMapping $mapping,
        private ApplicationDataResolution $dataResolution
    ) {
    }

    public function mapping(): NativeObjectActionMapping
    {
        return $this->mapping;
    }

    public function source(): ApplicationPath
    {
        return $this->mapping->source();
    }

    public function status(): string
    {
        return self::RESOLVED;
    }

    public function provenance(): string
    {
        return self::EXPLICIT;
    }

    public function dataResolution(): ApplicationDataResolution
    {
        return $this->dataResolution;
    }
}
