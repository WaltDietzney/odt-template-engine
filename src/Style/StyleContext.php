<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Style;

use DOMDocument;
use LogicException;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Utils\StyleMapper;

/**
 * Holds pending style requirements for one logical ODT document.
 *
 * Existing definitions in styles.xml remain authoritative document data. This
 * context only owns style requirements registered while the document is being
 * edited. Additional style families can be added as their migration semantics
 * are characterized.
 */
final class StyleContext
{
    private const STYLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    /** @var array<string, StyleRequirement> */
    private array $semanticDefinitions = [];

    /** @var list<StyleRequirement> */
    private array $semanticReferences = [];

    /** @var array<int, array{requirement: StyleRequirement, source: string}> */
    private array $referenceResolutions = [];

    /** @var array<int, list<array<string, mixed>>> */
    private array $referenceCandidates = [];

    /** @var array<int, list<array<string, mixed>>> */
    private array $ambiguousReferenceCandidates = [];

    public function __construct(
        private ?DOMDocument $contentDom = null,
        private ?DOMDocument $stylesDom = null
    ) {
    }

    /** @var array<string, array<string, mixed>> */
    private array $paragraphStyles = [];

    /** @var array<string, array<string, mixed>> */
    private array $textStyles = [];

    /** @var array<string, array<string, mixed>> */
    private array $frameStyles = [];

    /** @var array<string, array<string, mixed>> */
    private array $imageStyles = [];

    /** @var array<string, array<string, mixed>> */
    private array $fillImages = [];

    /**
     * Register one semantic requirement owned by this document.
     *
     * Definitions are keyed by their semantic identity. References are kept
     * as occurrences and re-evaluated whenever the document-local definition
     * set changes, making resolution independent of registration order.
     */
    public function registerRequirement(StyleRequirement $requirement): void
    {
        if ($requirement->kind() === StyleRequirement::KIND_DEFINITION) {
            $identity = $this->semanticIdentity($requirement);
            if (isset($this->semanticDefinitions[$identity])) {
                $existing = $this->semanticDefinitions[$identity];
                if (!$this->sameSemanticDefinition($existing, $requirement)) {
                    throw new LogicException(sprintf(
                        '%s style "%s" is already registered with a different definition (semantic requirements conflict).',
                        ucfirst($requirement->family()),
                        $requirement->name()
                    ));
                }
            } else {
                $this->semanticDefinitions[$identity] = $requirement;
            }
        } else {
            $this->semanticReferences[] = $requirement;
        }

        $this->refreshReferenceResolutions();
    }

    /** @return array<string, StyleRequirement> */
    public function semanticDefinitions(): array
    {
        return $this->semanticDefinitions;
    }

    /** @return list<StyleRequirement> */
    public function semanticReferences(): array
    {
        return $this->semanticReferences;
    }

    /** @return list<array{requirement: StyleRequirement, source: string}> */
    public function resolvedReferences(): array
    {
        return array_values($this->referenceResolutions);
    }

    /** @return list<StyleRequirement> */
    public function ambiguousReferences(): array
    {
        $references = [];
        foreach (array_keys($this->ambiguousReferenceCandidates) as $index) {
            $references[] = $this->semanticReferences[$index];
        }

        return $references;
    }

    /** @return list<array<string, mixed>> */
    public function ambiguousReferenceCandidates(StyleRequirement $reference): array
    {
        foreach ($this->semanticReferences as $index => $candidateReference) {
            if ($candidateReference === $reference) {
                return $this->ambiguousReferenceCandidates[$index] ?? [];
            }
        }

        return [];
    }

    /** @return StyleRequirement|array<string, mixed>|null */
    public function referenceCandidate(StyleRequirement $reference): StyleRequirement|array|null
    {
        foreach ($this->semanticReferences as $index => $candidateReference) {
            if ($candidateReference === $reference) {
                return $this->referenceCandidates[$index] ?? null;
            }
        }

        return null;
    }

    /** @return list<StyleRequirement> */
    public function unresolvedReferences(): array
    {
        $resolved = [];
        foreach (array_keys($this->referenceResolutions) as $index) {
            $resolved[$index] = true;
        }
        foreach (array_keys($this->ambiguousReferenceCandidates) as $index) {
            $resolved[$index] = true;
        }

        $unresolved = [];
        foreach ($this->semanticReferences as $index => $reference) {
            if (!isset($resolved[$index])) {
                $unresolved[] = $reference;
            }
        }

        return $unresolved;
    }

    public function referenceResolution(StyleRequirement $reference): ?string
    {
        foreach ($this->referenceResolutions as $resolution) {
            if ($resolution['requirement'] === $reference) {
                return $resolution['source'];
            }
        }

        return null;
    }

    /** Update the document parts consulted for existing style definitions. */
    public function replaceDocumentParts(DOMDocument $contentDom, DOMDocument $stylesDom): void
    {
        $this->contentDom = $contentDom;
        $this->stylesDom = $stylesDom;
        $this->refreshReferenceResolutions();
    }

    /**
     * Register one pending paragraph style definition.
     *
     * Re-registering an equivalent definition is idempotent. Reusing the same
     * name for a different definition is an explicit conflict.
     *
     * @param array<string, mixed> $definition
     */
    public function registerParagraphStyle(string $name, array $definition): void
    {
        if (!isset($this->paragraphStyles[$name])) {
            $this->paragraphStyles[$name] = $definition;

            return;
        }

        if ($this->paragraphStyles[$name] === $definition) {
            return;
        }

        throw new LogicException(sprintf(
            'Paragraph style "%s" is already registered with a different definition.',
            $name
        ));
    }

    /** @return array<string, array<string, mixed>> */
    public function paragraphStyles(): array
    {
        return $this->paragraphStyles;
    }

    /**
     * Register one pending text style definition for this document.
     *
     * @param array<string, mixed> $definition
     */
    public function registerTextStyle(string $name, array $definition): void
    {
        if (!isset($this->textStyles[$name])) {
            $this->textStyles[$name] = $definition;

            return;
        }

        if ($this->textStyles[$name] === $definition) {
            return;
        }

        throw new LogicException(sprintf(
            'Text style "%s" is already registered with a different definition.',
            $name
        ));
    }

    /** @return array<string, array<string, mixed>> */
    public function textStyles(): array
    {
        return $this->textStyles;
    }

    /**
     * Register one pending frame graphic style definition for this document.
     *
     * @param array<string, mixed> $definition
     */
    public function registerFrameStyle(string $name, array $definition): void
    {
        $this->registerGraphicRequirement($this->frameStyles, 'Frame style', $name, $definition);
    }

    /** @return array<string, array<string, mixed>> */
    public function frameStyles(): array
    {
        return $this->frameStyles;
    }

    /**
     * Register one pending image graphic style definition for this document.
     *
     * @param array<string, mixed> $definition
     */
    public function registerImageStyle(string $name, array $definition): void
    {
        $this->registerGraphicRequirement($this->imageStyles, 'Image style', $name, $definition);
    }

    /** @return array<string, array<string, mixed>> */
    public function imageStyles(): array
    {
        return $this->imageStyles;
    }

    /**
     * Register one pending fill-image declaration for this document.
     *
     * @param array<string, mixed> $definition
     */
    public function registerFillImage(string $name, array $definition): void
    {
        $this->registerGraphicRequirement($this->fillImages, 'Fill-image declaration', $name, $definition);
    }

    /** @return array<string, array<string, mixed>> */
    public function fillImages(): array
    {
        return $this->fillImages;
    }

    /**
     * Clear pending requirements after the logical document has been reset.
     */
    public function reset(): void
    {
        $this->semanticDefinitions = [];
        $this->semanticReferences = [];
        $this->referenceResolutions = [];
        $this->referenceCandidates = [];
        $this->ambiguousReferenceCandidates = [];
        $this->paragraphStyles = [];
        $this->textStyles = [];
        $this->frameStyles = [];
        $this->imageStyles = [];
        $this->fillImages = [];
    }

    private function semanticIdentity(StyleRequirement $requirement): string
    {
        return implode("\0", [
            $requirement->family(),
            $requirement->name(),
            $requirement->scope() ?? '',
            $requirement->documentPart() ?? '',
        ]);
    }

    private function sameSemanticDefinition(StyleRequirement $left, StyleRequirement $right): bool
    {
        return $left->kind() === $right->kind()
            && $left->scope() === $right->scope()
            && $left->family() === $right->family()
            && $left->documentPart() === $right->documentPart()
            && $left->name() === $right->name()
            && $left->parentStyleName() === $right->parentStyleName()
            && $left->propertyGroups() === $right->propertyGroups();
    }

    private function refreshReferenceResolutions(): void
    {
        $this->referenceResolutions = [];
        $this->referenceCandidates = [];
        $this->ambiguousReferenceCandidates = [];
        foreach ($this->semanticReferences as $index => $reference) {
            [$source, $candidates] = $this->resolveReference($reference);
            if (count($candidates) > 1) {
                $this->ambiguousReferenceCandidates[$index] = $candidates;
                continue;
            }

            if ($source !== null && $candidates !== []) {
                $this->referenceResolutions[$index] = [
                    'requirement' => $reference,
                    'source' => $source,
                ];
                $this->referenceCandidates[$index] = $candidates[0];
            }
        }
    }

    /** @return array{0: string|null, 1: list<array<string, mixed>>} */
    private function resolveReference(StyleRequirement $reference): array
    {
        $documentCandidates = $this->existingDocumentStyleCandidates($reference);
        if ($documentCandidates !== []) {
            return ['document', $documentCandidates];
        }

        $localCandidates = [];
        foreach ($this->semanticDefinitions as $definition) {
            if ($this->referenceMatchesDefinition($reference, $definition)) {
                $localCandidates[] = $definition;
            }
        }
        if ($localCandidates !== []) {
            return ['document-local', $localCandidates];
        }

        if ($this->legacyStyleExists($reference)) {
            return ['legacy', [$this->legacyCandidate($reference)]];
        }

        return [null, []];
    }

    private function referenceMatchesDefinition(
        StyleRequirement $reference,
        StyleRequirement $definition
    ): bool {
        return $reference->family() === $definition->family()
            && $reference->name() === $definition->name()
            && ($reference->scope() === null || $reference->scope() === $definition->scope())
            && ($reference->documentPart() === null
                || $reference->documentPart() === $definition->documentPart());
    }

    /** @return list<array<string, mixed>> */
    private function existingDocumentStyleCandidates(StyleRequirement $reference): array
    {
        $candidates = [];
        foreach ([StyleRequirement::PART_STYLES => $this->stylesDom, StyleRequirement::PART_CONTENT => $this->contentDom] as $part => $dom) {
            if (!$dom) {
                continue;
            }

            foreach ($dom->getElementsByTagNameNS(self::STYLE_NAMESPACE, 'style') as $style) {
                if ($style->getAttributeNS(self::STYLE_NAMESPACE, 'name') !== $reference->name()
                    || $style->getAttributeNS(self::STYLE_NAMESPACE, 'family') !== $reference->family()) {
                    continue;
                }

                $scope = $this->documentStyleScope($style);
                if ($reference->documentPart() !== null && $reference->documentPart() !== $part) {
                    continue;
                }
                if ($reference->scope() !== null && $reference->scope() !== $scope) {
                    continue;
                }

                $candidates[] = [
                    'source' => 'document',
                    'family' => $reference->family(),
                    'name' => $reference->name(),
                    'scope' => $scope,
                    'documentPart' => $part,
                ];
            }
        }

        return $candidates;
    }

    private function legacyStyleExists(StyleRequirement $reference): bool
    {
        if ($reference->family() === 'paragraph') {
            return array_key_exists($reference->name(), StyleMapper::getParagraphStyles());
        }

        if ($reference->family() === 'text') {
            return array_key_exists($reference->name(), StyleMapper::getTextStyles());
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function legacyCandidate(StyleRequirement $reference): array
    {
        return [
            'source' => 'legacy',
            'family' => $reference->family(),
            'name' => $reference->name(),
            'scope' => null,
            'documentPart' => null,
        ];
    }

    private function documentStyleScope(\DOMElement $style): ?string
    {
        $parent = $style->parentNode;
        while ($parent instanceof \DOMElement) {
            $localName = $parent->localName;
            if ($localName === 'styles' && $parent->namespaceURI === 'urn:oasis:names:tc:opendocument:xmlns:office:1.0') {
                return StyleRequirement::SCOPE_COMMON;
            }
            if ($localName === 'automatic-styles' && $parent->namespaceURI === 'urn:oasis:names:tc:opendocument:xmlns:office:1.0') {
                return StyleRequirement::SCOPE_AUTOMATIC;
            }
            $parent = $parent->parentNode;
        }

        return null;
    }

    /**
     * @param array<string, array<string, mixed>> $requirements
     * @param array<string, mixed> $definition
     */
    private function registerGraphicRequirement(
        array &$requirements,
        string $family,
        string $name,
        array $definition
    ): void {
        if (!isset($requirements[$name])) {
            $requirements[$name] = $definition;

            return;
        }

        if ($requirements[$name] === $definition) {
            return;
        }

        throw new LogicException(sprintf(
            '%s "%s" is already registered with a different definition.',
            $family,
            $name
        ));
    }
}
