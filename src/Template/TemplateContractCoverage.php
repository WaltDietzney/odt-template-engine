<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/** Immutable description of the bounded source regions inspected. */
final readonly class TemplateContractCoverage
{
    /**
     * @param list<array{source_part:string,region_kind:string,region_owner:?string,carrier_kind:string}> $inspectedRegions
     * @param list<string> $excludedParts
     */
    public function __construct(
        private array $inspectedRegions,
        private array $excludedParts = []
    ) {
    }

    /** @return list<array{source_part:string,region_kind:string,region_owner:?string,carrier_kind:string}> */
    public function inspectedRegions(): array
    {
        return $this->inspectedRegions;
    }

    /** @return list<string> */
    public function excludedParts(): array
    {
        return $this->excludedParts;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'inspected_regions' => $this->inspectedRegions,
            'excluded_parts' => $this->excludedParts,
        ];
    }
}
