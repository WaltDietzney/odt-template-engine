<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use InvalidArgumentException;

/**
 * Immutable semantic requirement for one document-local font-face identity.
 *
 * The ODF font-face identity and its font family are intentionally separate
 * values. This object does not resolve or materialize the dependency.
 */
final readonly class FontFaceRequirement
{
    public const PART_CONTENT = 'content.xml';

    public const PART_STYLES = 'styles.xml';

    public function __construct(
        private string $documentPart,
        private string $fontFaceName,
        private string $fontFamily
    ) {
        if (!in_array($this->documentPart, [self::PART_CONTENT, self::PART_STYLES], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported font-face requirement document part "%s".',
                $this->documentPart
            ));
        }
        if (trim($this->fontFaceName) === '') {
            throw new InvalidArgumentException('Font-face requirement identity must not be empty.');
        }
        if (trim($this->fontFamily) === '') {
            throw new InvalidArgumentException('Font-face requirement family must not be empty.');
        }
    }

    public function documentPart(): string
    {
        return $this->documentPart;
    }

    public function fontFaceName(): string
    {
        return $this->fontFaceName;
    }

    public function fontFamily(): string
    {
        return $this->fontFamily;
    }

    public function equals(self $other): bool
    {
        return $this->documentPart === $other->documentPart
            && $this->fontFaceName === $other->fontFaceName
            && $this->fontFamily === $other->fontFamily;
    }
}
