<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Raised when one typed native identity resolves to multiple descriptors.
 */
final class AmbiguousAddressableTargetException extends AddressableTargetException
{
    public function __construct(string $targetType, string $targetName)
    {
        parent::__construct(
            sprintf('Multiple %s targets match the name "%s".', $targetType, $targetName),
            $targetType,
            $targetName
        );
    }
}
