<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Resolves semantic font-face requirements against existing document XML.
 *
 * Resolution is read-only. It neither reconciles declarations nor
 * materializes missing ones.
 */
final class FontFaceRequirementResolver
{
    public const STATUS_SATISFIED = 'satisfied';

    public const STATUS_MISSING = 'missing';

    /**
     * @throws FontFaceResolutionConflictException
     */
    public function resolve(OdtDocumentContext $context, FontFaceRequirement $requirement): string
    {
        $matches = $this->matchingDeclarations($this->document($context, $requirement), $requirement);
        if ($matches === []) {
            return self::STATUS_MISSING;
        }

        foreach ($matches as $declaration) {
            $family = $declaration->getAttributeNS(
                'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
                'font-family'
            );
            if ($family === '' || $this->normalizeFamily($family) !== $this->normalizeFamily($requirement->fontFamily())) {
                throw new FontFaceResolutionConflictException(sprintf(
                    'Existing font-face identity "%s" in %s has an incompatible font family.',
                    $requirement->fontFaceName(),
                    $requirement->documentPart()
                ));
            }
        }

        return self::STATUS_SATISFIED;
    }

    /**
     * @param iterable<FontFaceRequirement> $requirements
     * @return list<FontFaceRequirement>
     */
    public function missing(OdtDocumentContext $context, iterable $requirements): array
    {
        $missing = [];
        foreach ($requirements as $requirement) {
            if ($this->resolve($context, $requirement) === self::STATUS_MISSING) {
                $missing[] = $requirement;
            }
        }

        return $missing;
    }

    private function document(OdtDocumentContext $context, FontFaceRequirement $requirement): DOMDocument
    {
        return $requirement->documentPart() === FontFaceRequirement::PART_CONTENT
            ? $context->contentDom()
            : $context->stylesDom();
    }

    /** @return list<DOMElement> */
    private function matchingDeclarations(DOMDocument $document, FontFaceRequirement $requirement): array
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        $root = $requirement->documentPart() === FontFaceRequirement::PART_CONTENT
            ? 'office:document-content'
            : 'office:document-styles';
        $nodes = $xpath->query('/' . $root . '/office:font-face-decls/style:font-face');
        if ($nodes === false) {
            return [];
        }

        $matches = [];
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            if ($node->getAttributeNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'name') === $requirement->fontFaceName()) {
                $matches[] = $node;
            }
        }

        return $matches;
    }

    private function normalizeFamily(string $family): string
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
