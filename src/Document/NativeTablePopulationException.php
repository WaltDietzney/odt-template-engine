<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use RuntimeException;

/**
 * Raised when a bounded native Writer table population cannot be performed.
 */
final class NativeTablePopulationException extends RuntimeException
{
    public function __construct(
        private readonly string $tableName,
        private readonly string $operation,
        private readonly string $reason
    ) {
        parent::__construct(sprintf(
            'Cannot perform %s for table "%s": %s.',
            $operation,
            $tableName,
            $reason
        ));
    }

    public function tableName(): string
    {
        return $this->tableName;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
