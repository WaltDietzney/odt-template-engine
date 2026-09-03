<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Owns pending font-face requirements for one logical document.
 */
final class FontFaceRequirementRegistry
{
    /** @var array<string, FontFaceRequirement> */
    private array $requirements = [];

    public function register(FontFaceRequirement $requirement): void
    {
        $key = $this->key($requirement);
        if (!isset($this->requirements[$key])) {
            $this->requirements[$key] = $requirement;
            return;
        }

        if ($this->requirements[$key]->equals($requirement)) {
            return;
        }

        throw new FontFaceRequirementConflictException(sprintf(
            'Font-face identity "%s" in %s is already registered for a different family.',
            $requirement->fontFaceName(),
            $requirement->documentPart()
        ));
    }

    /** @return list<FontFaceRequirement> */
    public function requirements(): array
    {
        return array_values($this->requirements);
    }

    public function reset(): void
    {
        $this->requirements = [];
    }

    private function key(FontFaceRequirement $requirement): string
    {
        return $requirement->documentPart() . "\0" . $requirement->fontFaceName();
    }
}
