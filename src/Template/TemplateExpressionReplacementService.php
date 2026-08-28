<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use DOMElement;
use DOMNode;

/** Applies evaluated expression values while preserving physical ODF nodes. */
final class TemplateExpressionReplacementService
{
    private const SCOPES = ['text:p', 'text:h', 'text:list-item', 'table:table-cell', 'table:covered-table-cell', 'text:section', 'draw:text-box'];

    /**
     * @param callable(string): ?string $evaluate Returns null when the token is not replaceable.
     */
    public function replace(DOMNode $root, callable $evaluate, bool $excludeNestedSections = false): void
    {
        foreach ($this->scopes($root) as $scope) {
            $events = [];
            $this->collect($scope, $events, $excludeNestedSections);
            $text = '';
            $fragments = [];
            foreach ($events as $event) {
                if ($event[0] === 'text') {
                    $start = strlen($text);
                    $text .= $event[1]->nodeValue ?? '';
                    $fragments[] = [$event[1], $start, strlen($text)];
                }
            }

            preg_match_all('/{{[^{}]*}}/', $text, $matches, PREG_OFFSET_CAPTURE);
            $tokens = $matches[0] ?? [];
            foreach (array_reverse($tokens) as [$token, $start]) {
                $replacement = $evaluate($token);
                if ($replacement === null) continue;
                $end = $start + strlen($token);
                $overlaps = [];
                foreach ($fragments as [$node, $fragmentStart, $fragmentEnd]) {
                    if ($fragmentEnd > $start && $fragmentStart < $end) {
                        $overlaps[] = [$node, $fragmentStart, $fragmentEnd];
                    }
                }
                if ($overlaps === []) continue;
                foreach ($overlaps as $index => [$node, $fragmentStart, $fragmentEnd]) {
                    $overlapStart = max($start, $fragmentStart);
                    $overlapEnd = min($end, $fragmentEnd);
                    $localStart = $overlapStart - $fragmentStart;
                    $localLength = $overlapEnd - $overlapStart;
                    $value = $node->nodeValue ?? '';
                    $insert = $index === 0 ? $replacement : '';
                    $node->nodeValue = substr($value, 0, $localStart)
                        . $insert
                        . substr($value, $localStart + $localLength);
                }
            }
        }
    }

    /** @return list<DOMElement> */
    private function scopes(DOMNode $root): array
    {
        $scopes = [];
        $walk = function (DOMNode $node) use (&$walk, &$scopes): void {
            if ($node instanceof DOMElement && in_array($node->nodeName, self::SCOPES, true)) $scopes[] = $node;
            foreach ($node->childNodes as $child) $walk($child);
        };
        $walk($root);
        return $scopes;
    }

    /** @param list<array{0:string,1:DOMNode,2?:?string}> $events */
    private function collect(DOMNode $node, array &$events, bool $excludeNestedSections = false): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && in_array($child->nodeName, self::SCOPES, true)) continue;
            if ($excludeNestedSections && $child instanceof DOMElement && $child->nodeName === 'text:section') continue;
            if ($child->nodeType === XML_TEXT_NODE) {
                $events[] = ['text', $child];
            } else {
                $this->collect($child, $events, $excludeNestedSections);
            }
        }
    }
}
