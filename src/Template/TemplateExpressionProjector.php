<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use DOMElement;
use DOMNode;

/**
 * Projects logical expressions from ODF text-flow scopes without changing the DOM.
 */
final class TemplateExpressionProjector
{
    private const BOUNDARIES = [
        'text:p', 'text:h', 'text:list-item', 'table:table-cell',
        'table:covered-table-cell', 'text:section', 'draw:text-box',
    ];

    /** @return list<TemplateExpressionDescriptor> */
    public function project(DOMNode $root, array &$diagnostics = []): array
    {
        $expressions = [];
        $openAtPreviousBoundary = false;
        foreach ($this->scopes($root) as $scope) {
            $events = [];
            $this->collect($scope, $events, true);
            $text = '';
            $textEvents = [];
            foreach ($events as $event) {
                if ($event[0] === 'text') {
                    $start = strlen($text);
                    $text .= $event[1]->nodeValue ?? '';
                    $textEvents[] = ['text', $event[1], $start, strlen($text), $event[2]];
                } else {
                    $event[1] = max(0, strlen($text));
                    $textEvents[] = $event;
                }
            }

            $this->unbalancedDiagnostics($text, $scope, $diagnostics);
            if ($openAtPreviousBoundary && str_contains($text, '}}')) {
                $diagnostics[] = $this->diagnostic(
                    'expression_crosses_text_flow_boundary',
                    'error',
                    'A template expression appears to continue across two text-flow scopes.',
                    null,
                    $scope,
                    'UNSAFE',
                    false
                );
            }
            $openAtPreviousBoundary = substr_count($text, '{{') > substr_count($text, '}}');
            preg_match_all('/{{([^{}]*)}}/', $text, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[0] as $index => [$raw, $start]) {
                $body = $matches[1][$index][0];
                $end = $start + strlen($raw);
                [$kind, $variable, $filter, $option] = $this->parse($body);
                $fragments = [];
                $styles = [];
                foreach ($textEvents as $event) {
                    if ($event[0] !== 'text' || $event[3] <= $start || $event[2] >= $end) continue;
                    $fragments[spl_object_id($event[1])] = true;
                    if ($event[4] !== null && $event[4] !== '') $styles[$event[4]] = true;
                }
                $bookmarks = [];
                foreach ($textEvents as $event) {
                    if ($event[0] === 'marker' && $event[1] >= $start && $event[1] <= $end && $event[2] !== '') {
                        $bookmarks[$event[2]] = true;
                    }
                }
                $split = count($fragments) > 1;
                $markerIntersection = $bookmarks !== [];
                $styleConflict = count($styles) > 1;
                $classification = $kind === 'UNSUPPORTED' ? 'UNSAFE' : ($split && !$styleConflict && !$markerIntersection ? 'REPAIRABLE' : 'VALID');
                $physical = ($styleConflict || $markerIntersection) ? 'UNSAFE' : ($split ? 'REPAIRABLE' : 'VALID');
                $expressionDiagnostics = [];
                if ($styleConflict) {
                    $expressionDiagnostics[] = 'style_conflict_in_expression';
                    $diagnostics[] = $this->diagnostic('style_conflict_in_expression', 'warning', 'Expression fragments use multiple declared text styles; physical normalization is unsafe.', $raw, $scope, 'VALID', false);
                }
                if ($markerIntersection) {
                    $expressionDiagnostics[] = 'bookmark_intersects_template_expression';
                    $diagnostics[] = $this->diagnostic('bookmark_intersects_template_expression', 'warning', 'Bookmark markers intersect the logical expression and must remain physically unchanged.', $raw, $scope, 'VALID', false);
                }
                if ($kind === 'UNSUPPORTED') {
                    $expressionDiagnostics[] = 'unsupported_template_expression';
                    $diagnostics[] = $this->diagnostic('unsupported_template_expression', 'error', 'Expression syntax is not supported by the current template processor.', $raw, $scope, 'UNSAFE', false);
                }
                $expressions[] = new TemplateExpressionDescriptor(
                    $raw, $kind, $variable, $filter, $option,
                    $this->scopeName($scope), count($fragments), array_keys($styles), array_keys($bookmarks),
                    $classification, $physical, $expressionDiagnostics
                );
            }
        }
        return $expressions;
    }

    /** @return list<DOMElement> */
    private function scopes(DOMNode $root): array
    {
        $scopes = [];
        $walk = function (DOMNode $node) use (&$walk, &$scopes): void {
            if ($node instanceof DOMElement && in_array($node->nodeName, self::BOUNDARIES, true)) {
                $scopes[] = $node;
            }
            foreach ($node->childNodes as $child) $walk($child);
        };
        $walk($root);
        return $scopes;
    }

    /** @param list<array{0:string,1:DOMNode|int,2?:string,3?:?string}> $events */
    private function collect(DOMNode $node, array &$events, bool $root = false): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && in_array($child->nodeName, self::BOUNDARIES, true)) continue;
            if ($child->nodeType === XML_TEXT_NODE) {
                $style = null;
                for ($parent = $child->parentNode; $parent !== null; $parent = $parent->parentNode) {
                    if ($parent instanceof DOMElement && $parent->nodeName === 'text:span') {
                        $style = $parent->getAttribute('text:style-name'); break;
                    }
                }
                $events[] = ['text', $child, $style];
            } elseif ($child instanceof DOMElement && in_array($child->nodeName, ['text:bookmark', 'text:bookmark-start', 'text:bookmark-end'], true)) {
                $events[] = ['marker', 0, $child->getAttribute('text:name')];
            } else {
                $this->collect($child, $events);
            }
        }
    }

    /** @return array{0:string,1:?string,2:?string,3:?string} */
    private function parse(string $body): array
    {
        if (preg_match('/^(\w+)$/', $body)) return ['SCALAR', $body, null, null];
        if (preg_match('/^(\w+):(\w+)(?:\|([^}]+))?$/', $body, $m)) {
            return in_array($m[1], ['nl2br', 'ul', 'ol'], true)
                ? ['SPECIAL', $m[2], $m[1], $m[3] ?? null]
                : ['FILTERED_SCALAR', $m[2], $m[1], $m[3] ?? null];
        }
        return match (true) {
            preg_match('/^#(?:if|ifnot|elseif):\w+$/', $body) === 1 => ['CONDITION_OPEN', substr($body, 1), null, null],
            $body === '#else' => ['CONDITION_ELSE', null, null, null],
            $body === '#endif' => ['CONDITION_END', null, null, null],
            preg_match('/^#foreach:\w+$/', $body) === 1 => ['FOREACH_OPEN', substr($body, 9), null, null],
            $body === '#endforeach' => ['FOREACH_END', null, null, null],
            default => ['UNSUPPORTED', null, null, null],
        };
    }

    private function unbalancedDiagnostics(string $text, DOMNode $scope, array &$diagnostics): void
    {
        $opens = substr_count($text, '{{'); $closes = substr_count($text, '}}');
        if ($opens === $closes) return;
        $diagnostics[] = $this->diagnostic(
            'malformed_template_expression', 'error', 'Template braces are unbalanced within one text-flow scope.', null, $scope, 'UNSAFE', false
        );
    }

    private function diagnostic(string $code, string $severity, string $message, ?string $expression, DOMNode $scope, string $classification, bool $repairable): TemplateStructureDiagnostic
    {
        return new TemplateStructureDiagnostic($code, $severity, $message, $classification, $repairable, $expression, $this->scopeName($scope));
    }

    private function scopeName(DOMNode $scope): string
    {
        if ($scope instanceof DOMElement) {
            if ($scope->nodeName === 'text:section') return 'section:' . $scope->getAttribute('text:name');
            return $scope->nodeName;
        }
        return 'document';
    }
}
