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
        DOMNode $replacement
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
}
