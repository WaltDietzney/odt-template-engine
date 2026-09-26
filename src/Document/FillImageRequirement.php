<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use InvalidArgumentException;

/**
 * Immutable document-local requirement for one named ODF fill-image declaration.
 *
 * The requirement owns only declaration semantics. Physical bitmap discovery,
 * copying, and manifest handling remain package/resource responsibilities.
 */
final readonly class FillImageRequirement
{
    public const PART_STYLES = 'styles.xml';

    public function __construct(
        private string $documentPart,
        private string $name,
        private string $href
    ) {
        if ($this->documentPart !== self::PART_STYLES) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported fill-image requirement document part "%s".',
                $this->documentPart
            ));
        }
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Fill-image requirement identity must not be empty.');
        }
        if (trim($this->href) === '') {
            throw new InvalidArgumentException('Fill-image requirement href must not be empty.');
        }
    }

    public function documentPart(): string
    {
        return $this->documentPart;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function href(): string
    {
        return $this->href;
    }

    public function equals(self $other): bool
    {
        return $this->documentPart === $other->documentPart
            && $this->name === $other->name
            && $this->href === $other->href;
    }
}
