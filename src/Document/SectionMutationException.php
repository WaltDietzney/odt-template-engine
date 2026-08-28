<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use RuntimeException;

/**
 * Raised when a valid section cannot safely accept replacement content.
 */
final class SectionMutationException extends RuntimeException
{
    public function __construct(
        private readonly string $sectionName,
        private readonly string $operation,
        private readonly string $reason,
        private readonly ?string $conflictingType = null,
        private readonly ?string $conflictingName = null
    ) {
        parent::__construct(sprintf(
            'Cannot perform %s for section "%s": %s.',
            $operation,
            $sectionName,
            $reason
        ));
    }

    public function sectionName(): string
    {
        return $this->sectionName;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function conflictingType(): ?string
    {
        return $this->conflictingType;
    }

    public function conflictingName(): ?string
    {
        return $this->conflictingName;
    }
}
