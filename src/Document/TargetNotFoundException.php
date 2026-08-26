<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Raised when a strict typed target lookup finds no matching identity.
 */
final class TargetNotFoundException extends AddressableTargetException
{
    public function __construct(string $targetType, string $targetName)
    {
        parent::__construct(
            sprintf('No %s target matches the name "%s".', $targetType, $targetName),
            $targetType,
            $targetName
        );
    }
}
