<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use RuntimeException;

/**
 * Base exception for strict addressable-target resolution failures.
 */
abstract class AddressableTargetException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $targetType,
        private readonly string $targetName
    ) {
        parent::__construct($message);
    }

    public function targetType(): string
    {
        return $this->targetType;
    }

    public function targetName(): string
    {
        return $this->targetName;
    }
}
