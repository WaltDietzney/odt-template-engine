<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use RuntimeException;

/** Public failure raised when a Writer User Field cannot be bound safely. */
final class UserFieldBindingException extends RuntimeException
{
    public const NOT_FOUND = 'NOT_FOUND';
    public const UNSUPPORTED_TYPE = 'UNSUPPORTED_TYPE';
    public const MALFORMED = 'MALFORMED';
    public const AMBIGUOUS = 'AMBIGUOUS';

    public function __construct(
        private readonly string $fieldName,
        private readonly string $reason,
        string $message
    ) {
        parent::__construct($message);
    }

    public function fieldName(): string
    {
        return $this->fieldName;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
