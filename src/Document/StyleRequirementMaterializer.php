<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Materializes resolved semantic style definitions.
 *
 * Semantic property groups are already native ODF data and are written
 * verbatim. Mapping and compatibility registries are deliberately outside
 * this service.
 */
final class StyleRequirementMaterializer
{
    private const OFFICE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const STYLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const NAMESPACES = [
        'office' => self::OFFICE_NAMESPACE,
        'style' => self::STYLE_NAMESPACE,
        'fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
        'text' => 'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
        'draw' => 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
        'svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
        'loext' => 'urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0',
        'xlink' => 'http://www.w3.org/1999/xlink',
    ];

    public function materialize(OdtDocumentContext $context, StyleRequirement $requirement): void
    {
        if (!in_array($requirement->family(), ['paragraph', 'text', 'graphic'], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported semantic style family "%s".',
                $requirement->family()
            ));
        }
        if ($requirement->kind() !== StyleRequirement::KIND_DEFINITION) {
            return;
        }
        if ($requirement->scope() === null || $requirement->documentPart() === null) {
            throw new InvalidArgumentException('Definitions must specify scope and document part.');
        }
        if ($requirement->scope() === StyleRequirement::SCOPE_COMMON
            && $requirement->documentPart() !== StyleRequirement::PART_STYLES) {
            throw new InvalidArgumentException(sprintf(
                'Common %s styles require styles.xml.',
                $requirement->family()
            ));
        }

        $dom = $requirement->documentPart() === StyleRequirement::PART_CONTENT
            ? $context->contentDom()
            : $context->stylesDom();
        $container = $this->container($dom, $requirement->scope());
        if ($this->styleExists($container, $requirement)) {
            return;
        }

        $style = $dom->createElementNS(self::STYLE_NAMESPACE, 'style:style');
        $this->setAttribute($style, 'style:name', $requirement->name());
        $this->setAttribute($style, 'style:family', $requirement->family());
        if ($requirement->parentStyleName() !== null) {
            $this->setAttribute($style, 'style:parent-style-name', $requirement->parentStyleName());
        }

        foreach ($requirement->propertyGroups() as $groupName => $properties) {
            $group = $this->createQualifiedElement($dom, $groupName);
            foreach ($properties as $attribute => $value) {
                if ($attribute === 'style:tab-stops' && is_array($value)) {
                    $tabStops = $this->createQualifiedElement($dom, 'style:tab-stops');
                    foreach ($value as $tabStopProperties) {
                        $tabStop = $this->createQualifiedElement($dom, 'style:tab-stop');
                        foreach ($tabStopProperties as $tabAttribute => $tabValue) {
                            $this->setAttribute($tabStop, (string) $tabAttribute, (string) $tabValue);
                        }
                        $tabStops->appendChild($tabStop);
                    }
                    $group->appendChild($tabStops);
                    continue;
                }
                if (is_array($value)) {
                    throw new InvalidArgumentException(sprintf(
                        'Unsupported array value for semantic style attribute "%s".',
                        $attribute
                    ));
                }
                $this->setAttribute($group, (string) $attribute, (string) $value);
            }
            $style->appendChild($group);
        }

        $container->appendChild($style);
    }

    private function container(DOMDocument $dom, string $scope): DOMElement
    {
        $wanted = $scope === StyleRequirement::SCOPE_COMMON ? 'styles' : 'automatic-styles';
        foreach ($dom->getElementsByTagNameNS(self::OFFICE_NAMESPACE, $wanted) as $container) {
            return $container;
        }

        $root = $dom->documentElement;
        if (!$root instanceof DOMElement) {
            throw new InvalidArgumentException('ODF document has no document element.');
        }
        $container = $dom->createElementNS(self::OFFICE_NAMESPACE, 'office:' . $wanted);
        $body = null;
        foreach ($root->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'body') {
                $body = $child;
                break;
            }
        }
        if ($body) {
            $root->insertBefore($container, $body);
        } else {
            $root->appendChild($container);
        }

        return $container;
    }

    private function styleExists(DOMElement $container, StyleRequirement $requirement): bool
    {
        foreach ($container->getElementsByTagNameNS(self::STYLE_NAMESPACE, 'style') as $style) {
            if ($style->getAttributeNS(self::STYLE_NAMESPACE, 'name') === $requirement->name()
                && $style->getAttributeNS(self::STYLE_NAMESPACE, 'family') === $requirement->family()) {
                return true;
            }
        }

        return false;
    }

    private function createQualifiedElement(DOMDocument $dom, string $qualifiedName): DOMElement
    {
        [$prefix] = explode(':', $qualifiedName, 2) + [null];
        if ($prefix === null || !isset(self::NAMESPACES[$prefix])) {
            throw new InvalidArgumentException(sprintf('Unsupported semantic ODF element "%s".', $qualifiedName));
        }

        return $dom->createElementNS(self::NAMESPACES[$prefix], $qualifiedName);
    }

    private function setAttribute(DOMElement $element, string $qualifiedName, string $value): void
    {
        if (!str_contains($qualifiedName, ':')) {
            $element->setAttribute($qualifiedName, $value);
            return;
        }

        [$prefix, $localName] = explode(':', $qualifiedName, 2);
        if ($prefix !== null && isset(self::NAMESPACES[$prefix])) {
            $element->setAttributeNS(self::NAMESPACES[$prefix], $qualifiedName, $value);
            return;
        }
        if ($prefix !== null) {
            throw new InvalidArgumentException(sprintf('Unsupported semantic ODF attribute "%s".', $qualifiedName));
        }
        $element->setAttribute($localName, $value);
    }
}
