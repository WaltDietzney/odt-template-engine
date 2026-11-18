<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Style;

use Closure;
use OdtTemplateEngine\Document\FontFaceRequirementDiscovery;
use OdtTemplateEngine\Document\FontFaceRequirementMaterializer;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementMaterializer;
use OdtTemplateEngine\Document\SectionStyleOptions;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\Utils\StyleMapper;
use OdtTemplateEngine\Utils\StyleOptionSplitter;

/**
 * Public document-oriented style authoring facade.
 *
 * The facade owns no style state. Every operation resolves the current
 * document context through the provider supplied by the owning OdtTemplate.
 */
final class DocumentStyles
{
    /** @var Closure(): OdtDocumentContext */
    private Closure $contextProvider;

    /**
     * @param Closure(): OdtDocumentContext $contextProvider
     */
    public function __construct(Closure $contextProvider, private ?Closure $packageProvider = null)
    {
        $this->contextProvider = $contextProvider;
    }

    /** Define a reusable two-stop gradient in the current document. */
    public function defineGradient(string $name, array $options): void
    {
        \OdtTemplateEngine\Document\FillResourceAuthoring::defineGradient(
            ($this->contextProvider)(), $name, $options
        );
    }

    /** Define a reusable local bitmap fill in the current document. */
    public function defineFillImage(string $name, string $imagePath): void
    {
        if ($this->packageProvider === null) {
            throw new \LogicException('Fill image authoring requires a package provider.');
        }
        \OdtTemplateEngine\Document\FillResourceAuthoring::defineImage(
            ($this->contextProvider)(), ($this->packageProvider)(), $name, $imagePath
        );
    }

    /**
     * Define a reusable named paragraph style for the current logical document.
     *
     * @param array<string, mixed> $options
     */
    public function defineParagraph(string $name, array $options): void
    {
        $split = StyleOptionSplitter::split($options, 'paragraph');
        $propertyGroups = [];

        if ($split['paragraph'] !== []) {
            $propertyGroups['style:paragraph-properties'] = StyleMapper::mapParagraphStyle(
                $split['paragraph']
            );
        }

        if ($split['text'] !== []) {
            $propertyGroups['style:text-properties'] = StyleMapper::mapTextStyleOptions(
                $split['text']
            );
        }

        $requirement = new StyleRequirement(
            StyleRequirement::KIND_DEFINITION,
            StyleRequirement::SCOPE_COMMON,
            'paragraph',
            StyleRequirement::PART_STYLES,
            $name,
            'Standard',
            $propertyGroups
        );

        $context = ($this->contextProvider)();
        $context->styleContext()->registerRequirement($requirement);

        $fontRequirement = (new FontFaceRequirementDiscovery())->discover($requirement);
        if ($fontRequirement !== null) {
            $context->registerFontFaceRequirement($fontRequirement);
            (new FontFaceRequirementMaterializer())->materialize($context, $fontRequirement);
        }

        (new StyleRequirementMaterializer())->materialize($context, $requirement);
    }

    /** Define a document-local automatic Writer Section style. */
    public function defineSection(string $name, array $options): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Section style name must not be empty.');
        }
        $propertyGroups = SectionStyleOptions::propertyGroups($options);
        $context = ($this->contextProvider)();
        $snapshot = $context->snapshotState();
        try {
            $this->assertSectionFamilyName($context, $name);
            if ($this->hasAuthoredSectionStyle($context, $name) && !$this->hasPendingSectionDefinition($context, $name)) {
                throw new \LogicException(sprintf(
                    'Section style "%s" already exists in the authored document; reuse it with SectionElement::setSectionStyle() instead of redefining it.',
                    $name
                ));
            }
            $requirement = new StyleRequirement(
                StyleRequirement::KIND_DEFINITION,
                StyleRequirement::SCOPE_AUTOMATIC,
                'section',
                StyleRequirement::PART_CONTENT,
                $name,
                null,
                $propertyGroups
            );
            $context->styleContext()->registerRequirement($requirement);
            (new StyleRequirementMaterializer())->materialize($context, $requirement);
        } catch (\Throwable $exception) {
            $context->restoreState($snapshot);
            throw $exception;
        }
    }

    private function hasAuthoredSectionStyle(OdtDocumentContext $context, string $name): bool
    {
        $styleNs = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        $officeNs = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
        $dom = $context->contentDom();
        foreach ($dom->getElementsByTagNameNS($styleNs, 'style') as $style) {
            if ($style->getAttributeNS($styleNs, 'name') !== $name
                || $style->getAttributeNS($styleNs, 'family') !== 'section') {
                continue;
            }
            $parent = $style->parentNode;
            while ($parent instanceof \DOMElement) {
                if ($parent->namespaceURI === $officeNs && $parent->localName === 'automatic-styles') {
                    return true;
                }
                $parent = $parent->parentNode;
            }
        }
        return false;
    }

    private function hasPendingSectionDefinition(OdtDocumentContext $context, string $name): bool
    {
        foreach ($context->styleContext()->semanticDefinitions() as $definition) {
            if ($definition->family() === 'section' && $definition->name() === $name) {
                return true;
            }
        }
        return false;
    }

    private function assertSectionFamilyName(OdtDocumentContext $context, string $name): void
    {
        $styleNs = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        foreach ([$context->contentDom(), $context->stylesDom()] as $dom) {
            foreach ($dom->getElementsByTagNameNS($styleNs, 'style') as $style) {
                if ($style->getAttributeNS($styleNs, 'name') === $name
                    && $style->getAttributeNS($styleNs, 'family') !== 'section'
                ) {
                    throw new \LogicException(sprintf(
                        'Style name "%s" already belongs to the "%s" family.',
                        $name,
                        $style->getAttributeNS($styleNs, 'family')
                    ));
                }
            }
        }
    }

    /**
     * Update an existing authored paragraph style in the current document.
     *
     * This is intentionally a mutation operation: existing Writer style
     * identity, inheritance, and unrelated properties remain intact while
     * supplied semantic paragraph/text properties are merged.
     *
     * @param array<string, mixed> $options
     */
    public function updateParagraph(string $name, array $options): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Paragraph style name must not be empty.');
        }

        $split = StyleOptionSplitter::split($options, 'paragraph');
        $paragraphProperties = $split['paragraph'] === []
            ? []
            : StyleMapper::mapParagraphStyle($split['paragraph']);
        $textProperties = $split['text'] === []
            ? []
            : StyleMapper::mapTextStyleOptions($split['text']);

        if ($paragraphProperties === [] && $textProperties === []) {
            return;
        }

        $context = ($this->contextProvider)();
        $matches = [];
        foreach ([$context->stylesDom(), $context->contentDom()] as $document) {
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
            foreach ($xpath->query(sprintf(
                '//style:style[@style:name=%s and @style:family="paragraph"]',
                $this->xpathLiteral($name)
            )) ?: [] as $match) {
                if ($match instanceof \DOMElement) {
                    $matches[] = $match;
                }
            }
        }
        if ($matches === []) {
            throw new \RuntimeException(sprintf('Paragraph style "%s" was not found.', $name));
        }
        if (count($matches) > 1) {
            throw new \RuntimeException(sprintf('Paragraph style "%s" is ambiguous.', $name));
        }

        $style = $matches[0];
        $this->mergeProperties($style->ownerDocument, $style, 'paragraph-properties', $paragraphProperties);
        $this->mergeProperties($style->ownerDocument, $style, 'text-properties', $textProperties);
    }

    /**
     * Apply document-wide Writer defaults to the named paragraph style "Standard".
     *
     * Existing authored properties are preserved unless the corresponding
     * option is explicitly supplied. Paragraph styles that inherit from
     * Standard continue to use Writer's native style inheritance.
     *
     * @param array{text?: array<string, mixed>, paragraph?: array<string, mixed>} $settings
     */
    public function setDocumentDefaults(array $settings): void
    {
        $textOptions = $settings['text'] ?? [];
        $paragraphOptions = $settings['paragraph'] ?? [];

        if (!is_array($textOptions) || !is_array($paragraphOptions)) {
            throw new \InvalidArgumentException('Document defaults require "text" and "paragraph" arrays.');
        }

        $context = ($this->contextProvider)();
        $dom = $context->stylesDom();
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');

        $standard = $xpath->query(
            '//office:styles/style:style[@style:family="paragraph" and @style:name="Standard"]'
        )->item(0);

        if (!$standard instanceof \DOMElement) {
            $officeStyles = $xpath->query('//office:styles')->item(0);
            if (!$officeStyles instanceof \DOMElement) {
                throw new \RuntimeException('ODF styles.xml has no <office:styles> container.');
            }

            $standard = $dom->createElementNS(
                'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                'style:style'
            );
            $standard->setAttributeNS(
                'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                'style:name',
                'Standard'
            );
            $standard->setAttributeNS(
                'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                'style:family',
                'paragraph'
            );
            $officeStyles->appendChild($standard);
        }

        $this->mergeProperties(
            $dom,
            $standard,
            'text-properties',
            StyleMapper::mapTextStyleOptions($textOptions)
        );
        $this->mergeProperties(
            $dom,
            $standard,
            'paragraph-properties',
            StyleMapper::mapParagraphStyle($paragraphOptions)
        );

        if ($textOptions !== []) {
            $requirement = new StyleRequirement(
                StyleRequirement::KIND_DEFINITION,
                StyleRequirement::SCOPE_COMMON,
                'paragraph',
                StyleRequirement::PART_STYLES,
                'Standard',
                null,
                ['style:text-properties' => StyleMapper::mapTextStyleOptions($textOptions)]
            );
            $fontRequirement = (new FontFaceRequirementDiscovery())->discover($requirement);
            if ($fontRequirement !== null) {
                $context->registerFontFaceRequirement($fontRequirement);
                (new FontFaceRequirementMaterializer())->materialize($context, $fontRequirement);
            }
        }
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function mergeProperties(
        \DOMDocument $dom,
        \DOMElement $style,
        string $localName,
        array $properties
    ): void {
        if ($properties === []) {
            return;
        }

        $namespace = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        $node = null;
        foreach ($style->childNodes as $child) {
            if ($child instanceof \DOMElement
                && $child->namespaceURI === $namespace
                && $child->localName === $localName
            ) {
                $node = $child;
                break;
            }
        }

        if (!$node instanceof \DOMElement) {
            $node = $dom->createElementNS($namespace, 'style:' . $localName);
            $style->appendChild($node);
        }

        $attributeNamespaces = [
            'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
            'style' => $namespace,
        ];

        foreach ($properties as $name => $value) {
            if (!is_scalar($value)) {
                throw new \InvalidArgumentException(
                    sprintf('Document default property "%s" must map to a scalar ODF attribute.', $name)
                );
            }

            [$prefix] = array_pad(explode(':', (string) $name, 2), 2, null);
            if (!isset($attributeNamespaces[$prefix])) {
                throw new \InvalidArgumentException(
                    sprintf('Unsupported document default attribute "%s".', $name)
                );
            }

            $node->setAttributeNS(
                $attributeNamespaces[$prefix],
                (string) $name,
                (string) $value
            );
        }
    }

    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        return 'concat(' . implode(', "\'", ', array_map(
            static fn (string $part): string => "'" . $part . "'",
            explode("'", $value)
        )) . ')';
    }

}
