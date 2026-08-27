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
