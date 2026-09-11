<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMDocument;
use DOMNode;
use DOMXPath;
use OdtTemplateEngine\Elements\OdtElement;

/**
 * Materializes constructed OdtElement content into existing document DOMs.
 *
 * This service deliberately does not own package state, style registries, or
 * template-language state. Compatibility callbacks are supplied by the
 * facade so protected override points remain observable during extraction.
 */
final class StructuredElementMaterializer
{
    /**
     * Inserts an element into the current content and styles document paths.
     *
     * @param callable(DOMDocument): void $normalize
     * @param callable(DOMDocument, string, DOMNode): void $replacePlaceholder
     * @param callable(DOMDocument, string): bool $hasPlaceholder
     */
    public function insert(
        DOMDocument $contentDom,
        DOMDocument $stylesDom,
        string $placeholder,
        OdtElement $element,
        callable $normalize,
        callable $replacePlaceholder,
        callable $hasPlaceholder
    ): void {
        $normalize($contentDom);
        $replacePlaceholder(
            $contentDom,
            $placeholder,
            $element->toDomNode($contentDom)
        );

        $normalize($stylesDom);
        while ($hasPlaceholder($stylesDom, $placeholder)) {
            $replacePlaceholder(
                $stylesDom,
                $placeholder,
                $element->toDomNode($stylesDom)
            );
        }
    }

    /**
     * Replaces a structured placeholder using the existing inline/block rules.
     *
     * Inline-compatible nodes remain inside the containing paragraph. Other
     * nodes replace the containing paragraph, including the historical special
     * handling for paragraphs inside draw:text-box.
     */
    public function replacePlaceholder(
        DOMDocument $dom,
        string $key,
        DOMNode $replacement,
        StructuredInsertionMode $insertionMode = StructuredInsertionMode::BLOCK
    ): void {
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//text()') as $textNode) {
            if (strpos($textNode->nodeValue, '{{' . $key . '}}') === false) {
                continue;
            }

            $parent = $textNode->parentNode;
            if (!$parent) {
                continue;
            }

            if (in_array($insertionMode, [
                StructuredInsertionMode::INLINE_TEXT_FLOW,
                StructuredInsertionMode::PRESERVE_TEXT_CONTAINER,
            ], true)) {
                $this->replaceInsideTextContainer(
                    $dom,
                    $textNode,
                    '{{' . $key . '}}',
                    $replacement
                );
                continue;
            }

            if (in_array($replacement->nodeName, ['text:span', 'text:s', 'text:line-break'], true)) {
                $parts = explode('{{' . $key . '}}', $textNode->nodeValue);
                $referenceNode = $textNode;

                foreach ($parts as $index => $part) {
                    if ($index > 0) {
                        $cloned = $replacement->cloneNode(true);
                        $parent->insertBefore($cloned, $referenceNode);
                    }

                    if ($part !== '') {
                        $newText = $dom->createTextNode($part);
                        $parent->insertBefore($newText, $referenceNode);
                    }
                }

                $parent->removeChild($referenceNode);
                continue;
            }

            $paragraphNode = $textNode;
            while ($paragraphNode && $paragraphNode->nodeName !== 'text:p') {
                $paragraphNode = $paragraphNode->parentNode;
            }

            if (!$paragraphNode) {
                continue;
            }

            $insideTextBox = false;
            $ancestor = $paragraphNode->parentNode;
            while ($ancestor) {
                if ($ancestor->nodeName === 'draw:text-box') {
                    $insideTextBox = true;
                    break;
                }
                $ancestor = $ancestor->parentNode;
            }

            $cloned = $replacement->cloneNode(true);
            if ($insideTextBox) {
                $paragraphNode->parentNode->insertBefore($cloned, $paragraphNode);
                $paragraphNode->parentNode->removeChild($paragraphNode);
            } else {
                $paragraphNode->parentNode->replaceChild($cloned, $paragraphNode);
            }
        }
    }

    private function replaceInsideTextContainer(
        DOMDocument $dom,
        DOMNode $textNode,
        string $placeholder,
        DOMNode $replacement
    ): void {
        $paragraph = $textNode->parentNode;
        while ($paragraph
            && !in_array($paragraph->nodeName, ['text:p', 'text:h'], true)
        ) {
            $paragraph = $paragraph->parentNode;
        }

        if (!$paragraph) {
            return;
        }

        $directChild = $textNode;
        while ($directChild->parentNode && $directChild->parentNode !== $paragraph) {
            $directChild = $directChild->parentNode;
        }

        $parts = explode($placeholder, (string) $textNode->nodeValue, 2);
        if (count($parts) !== 2) {
            return;
        }

        [$before, $after] = $parts;

        if ($directChild === $textNode) {
            if ($before !== '') {
                $paragraph->insertBefore(
                    $dom->createTextNode($before),
                    $directChild
                );
            }

            $paragraph->insertBefore(
                $replacement->cloneNode(true),
                $directChild
            );

            if ($after !== '') {
                $paragraph->insertBefore(
                    $dom->createTextNode($after),
                    $directChild
                );
            }

            $paragraph->removeChild($directChild);
            return;
        }

        if ($before === '' && $after !== '') {
            $textNode->nodeValue = $after;
            $paragraph->insertBefore(
                $replacement->cloneNode(true),
                $directChild
            );
            return;
        }

        if ($before !== '' && $after === '') {
            $textNode->nodeValue = $before;
            $paragraph->insertBefore(
                $replacement->cloneNode(true),
                $directChild->nextSibling
            );
            return;
        }

        if ($before === '' && $after === '') {
            $paragraph->insertBefore(
                $replacement->cloneNode(true),
                $directChild
            );
            $paragraph->removeChild($directChild);
            return;
        }

        // For a placeholder embedded between text in an inline wrapper, split
        // the top-level wrapper so the frame remains a direct text:p/text:h
        // child while preserving wrapper formatting on both text fragments.
        $beforeWrapper = $directChild->cloneNode(true);
        $afterWrapper = $directChild->cloneNode(true);

        $this->replaceDescendantText($beforeWrapper, $textNode, $before);
        $this->replaceDescendantText($afterWrapper, $textNode, $after);

        $paragraph->insertBefore($beforeWrapper, $directChild);
        $paragraph->insertBefore(
            $replacement->cloneNode(true),
            $directChild
        );
        $paragraph->insertBefore($afterWrapper, $directChild);
        $paragraph->removeChild($directChild);
    }

    private function replaceDescendantText(
        DOMNode $clonedRoot,
        DOMNode $originalTextNode,
        string $value
    ): void {
        $path = [];
        $node = $originalTextNode;

        while ($node->parentNode && $node->parentNode->nodeName !== 'text:p'
            && $node->parentNode->nodeName !== 'text:h'
        ) {
            $index = 0;
            for ($sibling = $node->previousSibling; $sibling; $sibling = $sibling->previousSibling) {
                ++$index;
            }
            array_unshift($path, $index);
            $node = $node->parentNode;
        }

        $target = $clonedRoot;
        foreach ($path as $index) {
            $target = $target->childNodes->item($index);
            if (!$target) {
                return;
            }
        }

        $target->nodeValue = $value;
    }

}
