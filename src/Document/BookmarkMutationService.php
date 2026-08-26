<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use DOMNode;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Performs the deliberately bounded first bookmark text mutation.
 */
final class BookmarkMutationService
{
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function replaceText(OdtDocumentContext $context, string $name, string $value): void
    {
        $this->validateValue($name, $value);
        [$start, $end] = $this->markers($context, $name);

        if ($start->parentNode === null || $start->parentNode !== $end->parentNode) {
            $this->fail($name, BookmarkDescriptor::TOPOLOGY_PARAGRAPH_SPANNING, 'bookmark range crosses text contexts');
        }

        $parent = $start->parentNode;
        if (!$parent instanceof DOMElement || !in_array($parent->nodeName, ['text:p', 'text:h', 'text:span'], true)) {
            $this->fail($name, BookmarkDescriptor::TOPOLOGY_MIXED_BLOCK, 'bookmark parent is not a supported text context');
        }

        $between = [];
        $current = $start->nextSibling;
        while ($current !== null && $current !== $end) {
            $between[] = $current;
            $current = $current->nextSibling;
        }
        if ($current !== $end) {
            $this->fail($name, BookmarkDescriptor::TOPOLOGY_MALFORMED, 'bookmark markers are not ordered as one range');
        }
        if ($between === []) {
            $this->fail($name, BookmarkDescriptor::TOPOLOGY_INLINE, 'empty paired bookmark range is not a text replacement');
        }
        foreach ($between as $node) {
            if ($node->nodeType !== XML_TEXT_NODE) {
                $this->fail($name, BookmarkDescriptor::TOPOLOGY_INLINE, 'selected range contains structured inline content');
            }
        }

        foreach ($between as $node) {
            $parent->removeChild($node);
        }
        $parent->insertBefore($context->contentDom()->createTextNode($value), $end);
    }

    /** @return array{DOMElement, DOMElement} */
    private function markers(OdtDocumentContext $context, string $name): array
    {
        $xpath = new \DOMXPath($context->contentDom());
        $xpath->registerNamespace('text', self::TEXT_NAMESPACE);
        foreach ($xpath->query('//text:bookmark') ?: [] as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
                $this->fail($name, BookmarkDescriptor::TOPOLOGY_COLLAPSED, 'collapsed bookmark has no selected text');
            }
        }
        $starts = [];
        $ends = [];
        foreach ($xpath->query('//text:bookmark-start') ?: [] as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
                $starts[] = $node;
            }
        }
        foreach ($xpath->query('//text:bookmark-end') ?: [] as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
                $ends[] = $node;
            }
        }
        if (count($starts) !== 1 || count($ends) !== 1) {
            throw new MalformedTargetException('bookmark', $name);
        }

        return [$starts[0], $ends[0]];
    }

    private function validateValue(string $name, string $value): void
    {
        if (preg_match('/[\r\n\t]/', $value) === 1) {
            $this->fail($name, BookmarkDescriptor::TOPOLOGY_INLINE, 'replacement text cannot contain newline or tab characters');
        }
        if (str_contains($value, '  ') || ($value !== '' && ($value[0] === ' ' || substr($value, -1) === ' '))) {
            $this->fail($name, BookmarkDescriptor::TOPOLOGY_INLINE, 'replacement text cannot contain leading, trailing, or repeated spaces');
        }
    }

    private function fail(string $name, string $topology, string $reason): never
    {
        throw new BookmarkMutationException($name, 'replaceText', $topology, $reason);
    }

}
