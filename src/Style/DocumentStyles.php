<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Style;

use Closure;
use OdtTemplateEngine\Document\FontFaceRequirementDiscovery;
use OdtTemplateEngine\Document\FontFaceRequirementMaterializer;
use OdtTemplateEngine\Document\StyleRequirement;
use OdtTemplateEngine\Document\StyleRequirementMaterializer;
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
    public function __construct(Closure $contextProvider)
    {
        $this->contextProvider = $contextProvider;
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

}
