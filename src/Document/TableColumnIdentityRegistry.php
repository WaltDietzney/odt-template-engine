<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMXPath;

/** @internal Document-local identities for generated table-column styles. */
final class TableColumnIdentityRegistry
{
    private const STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';

    public function __construct(private ?DOMDocument $document = null)
    {
    }

    public function replaceDocument(?DOMDocument $document): void
    {
        $this->document = $document;
    }

    /** @param array<string, StyleRequirement> $pending */
    public function allocate(StyleRequirement $requirement, array $pending = []): StyleRequirement
    {
        $properties = $this->properties($requirement);
        return $this->rename($requirement, $this->nextName($properties, $this->document, $pending));
    }

    /** @param array<string, string> $properties */
    public function resolveOrAllocate(DOMDocument $dom, array $properties): string
    {
        $name = $this->nextName($properties, $dom);
        foreach ($this->styles($dom) as $style) {
            if ($this->styleName($style) === $name) {
                return $name;
            }
        }
        if ($dom->documentElement instanceof DOMElement) {
            $this->write($dom, $name, $properties);
        }
        return $name;
    }

    /** @return array<string, string> */
    private function properties(StyleRequirement $requirement): array
    {
        $group = $requirement->propertyGroups()['style:table-column-properties'] ?? [];
        $properties = [];
        foreach ($group as $name => $value) {
            $properties[(string) $name] = (string) $value;
        }
        ksort($properties);
        return $properties;
    }

    /** @param array<string, string> $properties */
    private function key(array $properties): string
    {
        return hash('sha256', serialize(['table-column', 'automatic', 'content.xml', $properties]));
    }

    /** @param array<string, string> $properties */
    private function nextName(array $properties, ?DOMDocument $dom, array $pending = []): string
    {
        $candidate = 'co' . substr($this->key($properties), 0, 12);
        $suffix = 0;
        while ($this->occupied($candidate, $properties, $dom, $pending)) {
            $suffix++;
            $candidate = 'co' . substr($this->key($properties), 0, 12) . '_' . $suffix;
        }
        return $candidate;
    }

    /** @param array<string, string> $properties */
    private function occupied(string $name, array $properties, ?DOMDocument $dom, array $pending): bool
    {
        foreach ($pending as $entry) {
            if ($entry->name() === $name && $entry->family() === 'table-column'
                && $entry->scope() === StyleRequirement::SCOPE_AUTOMATIC
                && $entry->documentPart() === StyleRequirement::PART_CONTENT
                && ($entry->parentStyleName() !== null
                    || $entry->propertyGroups() !== ['style:table-column-properties' => $properties])) {
                return true;
            }
        }
        if ($dom === null) {
            return false;
        }
        $matches = 0;
        foreach ($this->styles($dom) as $style) {
            if ($this->styleName($style) === $name) {
                if (++$matches > 1 || !$this->matches($style, $properties)) {
                    return true;
                }
            }
        }
        return false;
    }

    /** @return list<DOMElement> */
    private function styles(DOMDocument $dom): array
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', self::OFFICE_NS);
        $xpath->registerNamespace('style', self::STYLE_NS);
        $styles = $xpath->query('//office:automatic-styles/style:style[@style:family="table-column"]');
        return $styles ? iterator_to_array($styles) : [];
    }

    private function styleName(DOMElement $style): string
    {
        return $style->getAttributeNS(self::STYLE_NS, 'name') ?: $style->getAttribute('style:name');
    }

    /** @param array<string, string> $properties */
    private function matches(DOMElement $style, array $properties): bool
    {
        foreach ($style->attributes as $attribute) {
            if ($attribute->namespaceURI !== self::STYLE_NS
                || !in_array($attribute->localName, ['name', 'family'], true)) {
                return false;
            }
        }
        $groups = 0;
        foreach ($style->childNodes as $child) {
            if ($child instanceof DOMElement) {
                if ($child->namespaceURI !== self::STYLE_NS || $child->localName !== 'table-column-properties') {
                    return false;
                }
                $groups++;
                if ($child->childElementCount !== 0) {
                    return false;
                }
            }
        }
        return $groups === 1 && $this->styleProperties($style) === $properties;
    }

    /** @return array<string, string> */
    private function styleProperties(DOMElement $style): array
    {
        $properties = [];
        foreach ($style->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->localName !== 'table-column-properties') {
                continue;
            }
            foreach ($child->attributes as $attribute) {
                $name = $attribute->namespaceURI === self::STYLE_NS
                    ? 'style:' . $attribute->localName
                    : $attribute->name;
                $properties[$name] = $attribute->value;
            }
        }
        ksort($properties);
        return $properties;
    }

    /** @param array<string, string> $properties */
    private function write(DOMDocument $dom, string $name, array $properties): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('office', self::OFFICE_NS);
        $container = $xpath->query('//office:automatic-styles')->item(0);
        if (!$container instanceof DOMElement) {
            $root = $dom->documentElement;
            if (!$root instanceof DOMElement) {
                throw new \LogicException('ODF document has no document element.');
            }
            $container = $dom->createElementNS(self::OFFICE_NS, 'office:automatic-styles');
            $root->insertBefore($container, $root->firstChild);
        }
        $style = $dom->createElementNS(self::STYLE_NS, 'style:style');
        $style->setAttributeNS(self::STYLE_NS, 'style:name', $name);
        $style->setAttributeNS(self::STYLE_NS, 'style:family', 'table-column');
        $column = $dom->createElementNS(self::STYLE_NS, 'style:table-column-properties');
        foreach ($properties as $property => $value) {
            $column->setAttributeNS(self::STYLE_NS, $property, $value);
        }
        $style->appendChild($column);
        $container->appendChild($style);
    }

    private function rename(StyleRequirement $requirement, string $name): StyleRequirement
    {
        return new StyleRequirement(
            $requirement->kind(), $requirement->scope(), $requirement->family(),
            $requirement->documentPart(), $name, $requirement->parentStyleName(),
            $requirement->propertyGroups()
        );
    }
}
