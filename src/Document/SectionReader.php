<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use DOMNode;
use DOMXPath;
use OdtTemplateEngine\OdtDocumentContext;

/**
 * Provides a conservative textual view of one native section.
 */
final class SectionReader
{
    private const TEXT_NAMESPACE = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

    public function text(OdtDocumentContext $context, string $name): string
    {
        $xpath = new DOMXPath($context->contentDom());
        $this->registerNamespaces($xpath);
        $sections = [];
        foreach ($xpath->query('//text:section[@text:name]') ?: [] as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('text:name') === $name) {
                $sections[] = $node;
            }
        }
        if ($sections === []) {
            throw new TargetNotFoundException('section', $name);
        }
        if (count($sections) > 1) {
            throw new AmbiguousAddressableTargetException('section', $name);
        }

        $lines = [];
        $this->collectBlockText($sections[0], $lines);

        return implode("\n", array_values(array_filter(
            array_map(static fn (string $line): string => trim($line), $lines),
            static fn (string $line): bool => $line !== ''
        )));
    }

    /** @param list<string> $lines */
    private function collectBlockText(DOMNode $node, array &$lines): void
    {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            if (in_array($child->nodeName, ['text:p', 'text:h'], true)) {
                $text = $this->inlineText($child);
                if ($text !== '') {
                    $lines[] = $text;
                }
                continue;
            }
            if ($child->nodeName === 'table:table-cell' && !$this->hasTextBlockDescendant($child)) {
                $text = $this->inlineText($child);
                if ($text !== '') {
                    $lines[] = $text;
                }
                continue;
            }
            if ($child->nodeName === 'draw:text-box' && !$this->hasTextBlockDescendant($child)) {
                $text = $this->inlineText($child);
                if ($text !== '') {
                    $lines[] = $text;
                }
                continue;
            }
            $this->collectBlockText($child, $lines);
        }
    }

    private function inlineText(DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->nodeValue;
                continue;
            }
            if ($child instanceof DOMElement && $child->nodeName !== 'draw:image') {
                $text .= $this->inlineText($child);
            }
        }

        return $text;
    }

    private function hasTextBlockDescendant(DOMElement $node): bool
    {
        foreach ($node->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'p') as $paragraph) {
            if ($paragraph !== $node) {
                return true;
            }
        }
        foreach ($node->getElementsByTagNameNS(self::TEXT_NAMESPACE, 'h') as $heading) {
            if ($heading !== $node) {
                return true;
            }
        }

        return false;
    }

    private function registerNamespaces(DOMXPath $xpath): void
    {
        $xpath->registerNamespace('text', self::TEXT_NAMESPACE);
    }
}
