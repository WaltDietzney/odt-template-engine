<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Raised when a discovered target is malformed for typed resolution.
 */
final class MalformedTargetException extends AddressableTargetException
{
    public function __construct(string $targetType, string $targetName)
    {
        parent::__construct(
            sprintf('The %s target "%s" is malformed and cannot be resolved.', $targetType, $targetName),
            $targetType,
            $targetName
        );
    }
}
