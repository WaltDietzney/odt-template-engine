<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use RuntimeException;

/**
 * Raised when an exact section clone cannot be prepared atomically.
 */
final class SectionCloneException extends RuntimeException
{
    public function __construct(
        private readonly string $sectionName,
        private readonly string $reason
    ) {
        parent::__construct(sprintf(
            'Cannot clone section "%s": %s.',
            $sectionName,
            $reason
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
}
