<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

/**
 * Discovers document-local font dependencies from native style semantics.
 *
 * This service is deliberately limited to semantic definitions. It does not
 * inspect legacy state, resolve existing declarations, or materialize XML.
 */
final class FontFaceRequirementDiscovery
{
    /**
     * Discover one font dependency, when both semantic font values are known.
     */
    public function discover(StyleRequirement $requirement): ?FontFaceRequirement
    {
        if ($requirement->kind() !== StyleRequirement::KIND_DEFINITION) {
            return null;
        }

        $properties = $requirement->propertyGroups()['style:text-properties'] ?? null;
        if (!is_array($properties)) {
            return null;
        }

        $fontFaceName = $properties['style:font-name'] ?? null;
        $fontFamily = $properties['fo:font-family'] ?? null;
        if (!is_scalar($fontFaceName) || !is_scalar($fontFamily)) {
            return null;
        }

        $fontFaceName = (string) $fontFaceName;
        $fontFamily = $this->unquoteFamily((string) $fontFamily);
        if (trim($fontFaceName) === '' || $fontFamily === '') {
            return null;
        }

        $documentPart = $requirement->documentPart();
        if ($documentPart === null) {
            return null;
        }

        return new FontFaceRequirement($documentPart, $fontFaceName, $fontFamily);
    }

    /**
     * @param iterable<StyleRequirement> $requirements
     * @return iterable<FontFaceRequirement>
     */
    public function discoverAll(iterable $requirements): iterable
    {
        foreach ($requirements as $requirement) {
            $fontRequirement = $this->discover($requirement);
            if ($fontRequirement !== null) {
                yield $fontRequirement;
            }
        }
    }

    private function unquoteFamily(string $family): string
    {
        $family = trim($family);
        if (strlen($family) >= 2) {
            $first = $family[0];
            $last = $family[strlen($family) - 1];
            if (($first === "'" && $last === "'") || ($first === '"' && $last === '"')) {
                return trim(substr($family, 1, -1));
            }
        }

        return $family;
    }
}
