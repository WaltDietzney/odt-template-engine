<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMElement;
use DOMNode;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Performs an exact structural clone of one native named section.
 *
 * This is an internal foundation for later identity-rewriting operations. It
 * intentionally produces duplicate native names and does not expose a public
 * facade clone API yet.
 */
final class SectionCloneService
{
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';

    /**
     * Clone the prototype section and rewrite its native/template identities.
     *
     * The source must be the unsuffixed prototype. The detached clone is
     * fully planned and rewritten before it is inserted into the document.
     */
    public function cloneWithRewrittenIdentities(OdtDocumentContext $context, string $name): DOMElement
    {
        if (preg_match('/_\d+$/', $name) === 1) {
            throw new SectionCloneException($name, 'only a prototype section may be cloned in this slice');
        }

        $source = $this->uniqueSection($context, $name);
        $clone = $source->cloneNode(true);
        if (!$clone instanceof DOMElement) {
            throw new SectionCloneException($name, 'native subtree could not be cloned');
        }

        $index = $this->nextCloneIndex($context, $source);
        $this->rewriteNativeIdentities($context, $source, $clone, $index);
        (new TemplateExpressionIdentityRewriter())->rewrite($clone, $index, $name);

        if (!$source->parentNode) {
            throw new SectionCloneException($name, 'source section has no parent insertion context');
        }

        try {
            $source->parentNode->insertBefore($clone, $source->nextSibling);
        } catch (\Throwable $exception) {
            throw new SectionCloneException($name, 'rewritten subtree could not be inserted atomically');
        }

        return $clone;
    }

    public function cloneExact(OdtDocumentContext $context, string $name): DOMElement
    {
        $matches = [];
        foreach ($context->contentDom()->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'section') as $node) {
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

        return $this->cloneAfter($matches[0]);
    }

    /**
     * Clone a previously selected source node. This is an internal test and
     * orchestration seam used when duplicate exact-clone names are expected.
     */
    public function cloneExactSource(DOMElement $source): DOMElement
    {
        return $this->cloneAfter($source);
    }

    private function cloneAfter(DOMElement $source): DOMElement
    {
        $name = $source->getAttribute('text:name');
        $this->validateTechnicalIds($source, $name);
        $clone = $source->cloneNode(true);
        if (!$clone instanceof DOMElement) {
            throw new SectionCloneException($name, 'native subtree could not be cloned');
        }
        if (!$source->parentNode) {
            throw new SectionCloneException($name, 'source section has no parent insertion context');
        }

        try {
            $source->parentNode->insertBefore($clone, $source->nextSibling);
        } catch (\Throwable $exception) {
            throw new SectionCloneException($name, 'native subtree could not be inserted atomically');
        }

        return $clone;
    }

    private function uniqueSection(OdtDocumentContext $context, string $name): DOMElement
    {
        $matches = [];
        foreach ($context->contentDom()->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'section') as $node) {
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

    private function nextCloneIndex(OdtDocumentContext $context, DOMElement $source): int
    {
        $prototypeNames = $this->identityNames($source);
        $occupied = $this->allNativeNames($context->contentDom(), $source);
        $index = 1;
        while (true) {
            foreach ($prototypeNames as $name) {
                [$type, $identity] = explode('|', $name, 2);
                if (isset($occupied[$type . '|' . $identity . '_' . $index])) {
                    $index++;
                    continue 2;
                }
            }
            return $index;
        }
    }

    /** @return list<string> */
    private function identityNames(DOMElement $source): array
    {
        $attributes = ['text:name', 'table:name', 'draw:name'];
        $names = [];
        $nodes = [$source];
        foreach ($source->getElementsByTagName('*') as $node) {
            $nodes[] = $node;
        }
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            foreach ($attributes as $attribute) {
                if ($node->hasAttribute($attribute) && $node->getAttribute($attribute) !== '') {
                    $names[$node->nodeName . '|' . $node->getAttribute($attribute)] = true;
                }
            }
        }
        return array_keys($names);
    }

    /** @return array<string, bool> */
    private function allNativeNames(DOMDocument $dom, DOMElement $source): array
    {
        $attributes = ['text:name', 'table:name', 'draw:name'];
        $occupied = [];
        foreach ($dom->getElementsByTagName('*') as $node) {
            if (!$node instanceof DOMElement || $this->inside($node, $source)) {
                continue;
            }
            foreach ($attributes as $attribute) {
                if ($node->hasAttribute($attribute) && $node->getAttribute($attribute) !== '') {
                    $occupied[$node->nodeName . '|' . $node->getAttribute($attribute)] = true;
                }
            }
        }
        return $occupied;
    }

    private function rewriteNativeIdentities(
        OdtDocumentContext $context,
        DOMElement $source,
        DOMElement $clone,
        int $index
    ): void {
        $attributes = [
            'text:section' => 'text:name',
            'text:bookmark' => 'text:name',
            'text:bookmark-start' => 'text:name',
            'text:bookmark-end' => 'text:name',
            'table:table' => 'table:name',
            'draw:frame' => 'draw:name',
            'draw:custom-shape' => 'draw:name',
        ];
        $occupied = $this->nativeNamesOutside($context->contentDom(), $source, $attributes);
        $rewrites = [];

        $nodes = [$clone];
        foreach ($clone->getElementsByTagName('*') as $node) {
            $nodes[] = $node;
        }
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement || !isset($attributes[$node->nodeName])) {
                continue;
            }
            $attribute = $attributes[$node->nodeName];
            $old = $node->getAttribute($attribute);
            if ($old === '') {
                continue;
            }
            $key = $node->nodeName . ':' . $old;
            if (!isset($rewrites[$key])) {
                $candidate = $old . '_' . $index;
                $counter = $index;
                while (isset($occupied[$node->nodeName][$candidate])) {
                    $counter++;
                    $candidate = $old . '_' . $counter;
                }
                $rewrites[$key] = $candidate;
                $occupied[$node->nodeName][$candidate] = true;
            }
            $node->setAttribute($attribute, $rewrites[$key]);
        }

        $this->rewriteTechnicalIds($context, $source, $clone, $index);
    }

    /** @param array<string, string> $attributes */
    private function nativeNamesOutside(DOMDocument $dom, DOMElement $source, array $attributes): array
    {
        $occupied = [];
        foreach ($dom->getElementsByTagName('*') as $node) {
            if (!$node instanceof DOMElement || $this->inside($node, $source)) {
                continue;
            }
            if (!isset($attributes[$node->nodeName])) {
                continue;
            }
            $name = $node->getAttribute($attributes[$node->nodeName]);
            if ($name !== '') {
                $occupied[$node->nodeName][$name] = true;
            }
        }
        return $occupied;
    }

    private function rewriteTechnicalIds(
        OdtDocumentContext $context,
        DOMElement $source,
        DOMElement $clone,
        int $index
    ): void {
        $occupied = [];
        foreach ($context->contentDom()->getElementsByTagName('*') as $node) {
            if ($node instanceof DOMElement && !$this->inside($node, $source) && $node->hasAttributeNS(self::XML_NAMESPACE, 'id')) {
                $occupied[$node->getAttributeNS(self::XML_NAMESPACE, 'id')] = true;
            }
        }
        $ids = [];
        $nodes = [$clone];
        foreach ($clone->getElementsByTagName('*') as $node) {
            $nodes[] = $node;
        }
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement || !$node->hasAttributeNS(self::XML_NAMESPACE, 'id')) {
                continue;
            }
            $old = $node->getAttributeNS(self::XML_NAMESPACE, 'id');
            $candidate = $old . '_' . $index;
            $counter = $index;
            while (isset($occupied[$candidate])) {
                $counter++;
                $candidate = $old . '_' . $counter;
            }
            $ids[$old] = $candidate;
            $occupied[$candidate] = true;
            $node->setAttributeNS(self::XML_NAMESPACE, 'xml:id', $candidate);
        }
        if ($ids === []) {
            return;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            foreach ($node->attributes as $attribute) {
                $value = $attribute->value;
                if (isset($ids[$value])) {
                    $attribute->value = $ids[$value];
                } elseif (str_starts_with($value, '#') && isset($ids[substr($value, 1)])) {
                    $attribute->value = '#' . $ids[substr($value, 1)];
                }
            }
        }
    }

    private function validateTechnicalIds(DOMElement $source, string $name): void
    {
        $ids = [];
        $walk = function (DOMNode $node) use (&$walk, &$ids, $name): void {
            if ($node instanceof DOMElement && $node->hasAttributeNS(self::XML_NAMESPACE, 'id')) {
                $id = $node->getAttributeNS(self::XML_NAMESPACE, 'id');
                if (isset($ids[$id])) {
                    throw new SectionCloneException($name, sprintf('source contains duplicate xml:id "%s"', $id));
                }
                $ids[$id] = true;
            }
            foreach ($node->childNodes as $child) {
                $walk($child);
            }
        };
        $walk($source);

        $document = $source->ownerDocument;
        if (!$document) {
            throw new SectionCloneException($name, 'source section has no owner document');
        }
        foreach ($document->getElementsByTagName('*') as $node) {
            if (!$node instanceof DOMElement || !$node->hasAttributeNS(self::XML_NAMESPACE, 'id')) {
                continue;
            }
            if ($this->inside($node, $source)) {
                continue;
            }
            if (isset($ids[$node->getAttributeNS(self::XML_NAMESPACE, 'id')])) {
                throw new SectionCloneException($name, 'clone would duplicate an existing xml:id');
            }
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
}
