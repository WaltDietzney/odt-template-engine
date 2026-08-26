<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\OdtDocumentContext;
use OdtTemplateEngine\OdtPackage;

/**
 * Replaces the children of one native section with bounded structured content.
 */
final class SectionMutationService
{
    private const DRAWING_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
    private const TABLE_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function replaceContent(
        OdtDocumentContext $context,
        string $name,
        OdtElement $content,
        ?OdtPackage $package = null
    ): void
    {
        $section = $this->findSection($context->contentDom(), $name);
        $staging = $context->contentDom()->cloneNode(true);
        if (!$staging instanceof DOMDocument) {
            $this->fail($name, 'unable to create a detached materialization document');
        }

        $stagedSection = $this->findSection($staging, $name);
        $replacement = $content->toDomNode($staging);
        $nodes = $this->replacementNodes($replacement, $name);
        $this->validateNames($context->contentDom(), $section, $nodes, $name);

        $assets = $content->getImageAssets();
        $containsImage = false;
        foreach ($nodes as $node) {
            if ($this->containsImageAsset($node)) {
                $containsImage = true;
                break;
            }
        }
        if ($containsImage && $assets === []) {
            $this->fail($name, 'resource-bearing content does not expose package assets');
        }
        if ($assets !== [] && $package === null) {
            $this->fail($name, 'resource-bearing content requires package ownership');
        }
        $createdResources = $package?->copyImageResourcesAtomically($assets) ?? [];
        $originalChildren = [];
        foreach ($section->childNodes as $child) {
            $originalChildren[] = $child->cloneNode(true);
        }

        try {
            while ($stagedSection->firstChild !== null) {
                $stagedSection->removeChild($stagedSection->firstChild);
            }
            foreach ($nodes as $node) {
                $stagedSection->appendChild($node);
            }

            while ($section->firstChild !== null) {
                $section->removeChild($section->firstChild);
            }
            foreach ($nodes as $node) {
                $section->appendChild($this->copyNode($context->contentDom(), $node));
            }
        } catch (\Throwable $exception) {
            while ($section->firstChild !== null) {
                $section->removeChild($section->firstChild);
            }
            foreach ($originalChildren as $child) {
                $section->appendChild($this->copyNode($context->contentDom(), $child));
            }
            $package?->removePreparedPackageFiles($createdResources);
            throw $exception;
        }
    }

    private function findSection(DOMDocument $dom, string $name): DOMElement
    {
        $matches = [];
        foreach ($dom->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'section') as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
                $matches[] = $node;
            }
        }
        if ($matches === []) {
            throw new TargetNotFoundException('section', $name);
        }
        if (count($matches) > 1) {
            throw new AmbiguousAddressableTargetException('section', $name);
        }

        return $matches[0];
    }

    /** @return list<DOMElement> */
    private function replacementNodes(DOMNode $replacement, string $name): array
    {
        $nodes = [];
        $candidates = $replacement instanceof DOMElement ? [$replacement] : iterator_to_array($replacement->childNodes);
        foreach ($candidates as $node) {
            if (!$node instanceof DOMElement) {
                $this->fail($name, 'replacement contains non-element top-level content');
            }
            if (!in_array($node->nodeName, ['text:p', 'text:h', 'text:list', 'table:table', 'draw:frame'], true)) {
                $this->fail($name, 'replacement contains a node that is not legal section block content');
            }
            $nodes[] = $node;
        }

        return $nodes;
    }

    /** @param list<DOMElement> $nodes */
    private function validateNames(DOMDocument $original, DOMElement $section, array $nodes, string $sectionName): void
    {
        $existing = $this->namedIdentities($original, $section);
        $this->validateBookmarkPairs($nodes, $sectionName);
        $introduced = [];
        foreach ($nodes as $node) {
            foreach ($this->namedIdentitiesInSubtree($node) as [$type, $name]) {
                if ($name === '') {
                    if ($type === 'frame') {
                        continue;
                    }
                    $this->fail($sectionName, 'replacement contains a named native object without an identity');
                }
                $key = $type . ':' . $name;
                if (isset($introduced[$key]) || isset($existing[$key])) {
                    throw new SectionMutationException(
                        $sectionName,
                        'replaceContent',
                        'replacement introduces a same-type native identity collision',
                        $type,
                        $name
                    );
                }
                $introduced[$key] = true;
            }
        }
    }

    /** @return array<string, true> */
    private function namedIdentities(DOMDocument $dom, DOMElement $excludedSection): array
    {
        $identities = [];
        foreach ($dom->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'section') as $node) {
            if ($node instanceof DOMElement && !$this->inside($node, $excludedSection) && $node !== $excludedSection) {
                $this->addIdentity($identities, 'section', $node->getAttribute('text:name'));
            }
        }
        foreach ($dom->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'bookmark-start') as $node) {
            if ($node instanceof DOMElement && !$this->inside($node, $excludedSection)) {
                $this->addIdentity($identities, 'bookmark', $node->getAttribute('text:name'));
            }
        }
        foreach ($dom->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'bookmark') as $node) {
            if ($node instanceof DOMElement && !$this->inside($node, $excludedSection)) {
                $this->addIdentity($identities, 'bookmark', $node->getAttribute('text:name'));
            }
        }
        foreach ($dom->getElementsByTagNameNS(self::TABLE_NAMESPACE, 'table') as $node) {
            if ($node instanceof DOMElement && !$this->inside($node, $excludedSection)) {
                $this->addIdentity($identities, 'table', $node->getAttribute('table:name'));
            }
        }
        foreach ($dom->getElementsByTagNameNS(self::DRAWING_NAMESPACE, 'frame') as $node) {
            if ($node instanceof DOMElement && !$this->inside($node, $excludedSection)) {
                $this->addIdentity($identities, 'frame', $node->getAttribute('draw:name'));
            }
        }

        return $identities;
    }

    /** @return list<array{string, string}> */
    private function namedIdentitiesInSubtree(DOMElement $node): array
    {
        $identities = [];
        $walk = function (DOMNode $current) use (&$walk, &$identities): void {
            if ($current instanceof DOMElement) {
                $identity = match ($current->nodeName) {
                    'text:section' => ['section', $current->getAttribute('text:name')],
                    'text:bookmark-start', 'text:bookmark' => ['bookmark', $current->getAttribute('text:name')],
                    'table:table' => ['table', $current->getAttribute('table:name')],
                    'draw:frame' => ['frame', $current->getAttribute('draw:name')],
                    default => null,
                };
                if ($identity !== null) {
                    $identities[] = $identity;
                }
            }
            foreach ($current->childNodes as $child) {
                $walk($child);
            }
        };
        $walk($node);

        return $identities;
    }

    /** @param array<string, true> $identities */
    private function addIdentity(array &$identities, string $type, string $name): void
    {
        if ($name !== '') {
            $identities[$type . ':' . $name] = true;
        }
    }

    private function inside(DOMNode $node, DOMElement $ancestor): bool
    {
        for ($current = $node->parentNode; $current !== null; $current = $current->parentNode) {
            if ($current === $ancestor) {
                return true;
            }
        }

        return false;
    }

    private function containsImageAsset(DOMNode $node): bool
    {
        if ($node instanceof DOMElement && $node->nodeName === 'draw:image') {
            return true;
        }
        foreach ($node->childNodes as $child) {
            if ($this->containsImageAsset($child)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<DOMElement> $nodes */
    private function validateBookmarkPairs(array $nodes, string $sectionName): void
    {
        $markers = [];
        $walk = function (DOMNode $node) use (&$walk, &$markers): void {
            if ($node instanceof DOMElement) {
                $type = match ($node->nodeName) {
                    'text:bookmark' => 'collapsed',
                    'text:bookmark-start' => 'start',
                    'text:bookmark-end' => 'end',
                    default => null,
                };
                if ($type !== null) {
                    $name = $node->getAttribute('text:name');
                    $markers[$name][$type] = ($markers[$name][$type] ?? 0) + 1;
                }
            }
            foreach ($node->childNodes as $child) {
                $walk($child);
            }
        };
        foreach ($nodes as $node) {
            $walk($node);
        }
        foreach ($markers as $name => $counts) {
            $collapsed = $counts['collapsed'] ?? 0;
            $starts = $counts['start'] ?? 0;
            $ends = $counts['end'] ?? 0;
            if ($name === '' || ($collapsed !== 0 && ($collapsed !== 1 || $starts !== 0 || $ends !== 0))
                || ($collapsed === 0 && ($starts !== 1 || $ends !== 1))) {
                $this->fail($sectionName, 'replacement contains a malformed bookmark identity');
            }
        }
    }

    private function fail(string $sectionName, string $reason): never
    {
        throw new SectionMutationException($sectionName, 'replaceContent', $reason);
    }

    private function copyNode(DOMDocument $document, DOMNode $node): DOMNode
    {
        if (!$node instanceof DOMElement) {
            return $document->importNode($node, true);
        }

        $namespaces = [
            'draw' => self::DRAWING_NAMESPACE,
            'table' => self::TABLE_NAMESPACE,
            'text' => self::TEXT_NAMESPACE,
            'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
            'xlink' => 'http://www.w3.org/1999/xlink',
        ];
        $prefix = str_contains($node->nodeName, ':') ? explode(':', $node->nodeName, 2)[0] : null;
        $copy = $prefix !== null && isset($namespaces[$prefix])
            ? $document->createElementNS($namespaces[$prefix], $node->nodeName)
            : $document->createElement($node->nodeName);
        foreach ($node->attributes as $attribute) {
            if ($attribute->namespaceURI !== null) {
                $copy->setAttributeNS($attribute->namespaceURI, $attribute->nodeName, $attribute->nodeValue);
            } else {
                $copy->setAttribute($attribute->nodeName, $attribute->nodeValue);
            }
        }
        foreach ($node->childNodes as $child) {
            $copy->appendChild($this->copyNode($document, $child));
        }

        return $copy;
    }
}
