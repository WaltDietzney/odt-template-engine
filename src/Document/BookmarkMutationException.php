<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use RuntimeException;

/**
 * Raised when a bookmark is valid but cannot safely perform a text mutation.
 */
final class BookmarkMutationException extends RuntimeException
{
    public function __construct(
        private readonly string $bookmarkName,
        private readonly string $operation,
        private readonly string $topology,
        private readonly string $reason
    ) {
        parent::__construct(sprintf(
            'Cannot perform %s for bookmark "%s": %s.',
            $operation,
            $bookmarkName,
            $reason
        ));
    }

    public function bookmarkName(): string
    {
        return $this->bookmarkName;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function topology(): string
    {
        return $this->topology;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
