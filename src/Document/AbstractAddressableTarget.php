<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtPackage;

/**
 * Shared identity-backed mechanics for typed, read-only target handles.
 *
 * This is an implementation base, not a public universal target abstraction:
 * concrete target types expose their own descriptor contracts.
 */
abstract class AbstractAddressableTarget
{
    public function __construct(
        protected readonly OdtDocumentContext $context,
        protected readonly string $targetName,
        protected readonly ?OdtPackage $package = null
    ) {
    }

    public function name(): string
    {
        return $this->targetName;
    }
}
