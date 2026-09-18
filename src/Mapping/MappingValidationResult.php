<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

final readonly class MappingValidationResult
{
    /** @param list<MappingDiagnostic> $diagnostics
     *  @param list<DeferredMappingCheck> $deferredChecks
     */
    public function __construct(private array $diagnostics, private array $deferredChecks)
    {
    }

    public function valid(): bool
    {
        return $this->diagnostics === [];
    }

    /** @return list<MappingDiagnostic> */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    /** @return list<DeferredMappingCheck> */
    public function deferredChecks(): array
    {
        return $this->deferredChecks;
    }
}
