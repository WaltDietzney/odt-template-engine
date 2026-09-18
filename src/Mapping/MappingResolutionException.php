<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Signals that explicit mappings failed the existing static validation gate. */
final class MappingResolutionException extends \InvalidArgumentException
{
    public function __construct(private MappingValidationResult $validationResult)
    {
        parent::__construct('MappingDefinition is statically invalid and cannot be resolved.');
    }

    public function validationResult(): MappingValidationResult
    {
        return $this->validationResult;
    }
}
