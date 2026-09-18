<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Source binding for an explicit document-capability mapping. */
final readonly class DocumentCapabilityResolution
{
    public const RESOLVED = 'RESOLVED';
    public const EXPLICIT = 'EXPLICIT';

    public function __construct(
        private DocumentCapabilityMapping $mapping,
        private ApplicationDataResolution $dataResolution
    ) {
    }

    public function mapping(): DocumentCapabilityMapping
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
