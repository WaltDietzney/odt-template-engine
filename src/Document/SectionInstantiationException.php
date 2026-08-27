<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use RuntimeException;

/** Raised when clone-local section instantiation cannot be completed. */
final class SectionInstantiationException extends RuntimeException
{
    public function __construct(
        private readonly string $sectionName,
        private readonly string $reason,
        private readonly ?string $variableName = null
    ) {
        parent::__construct(sprintf(
            'Cannot instantiate section "%s": %s%s.',
            $sectionName,
            $reason,
            $variableName === null ? '' : sprintf(' (%s)', $variableName)
        ));
    }

    public function sectionName(): string
    {
        return $this->sectionName;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function variableName(): ?string
    {
        return $this->variableName;
    }
}
