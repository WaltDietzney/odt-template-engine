<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Applies only preservation-safe physical template repairs.
 *
 * In particular, equivalent adjacent text spans are joined only when they
 * comprise a complete logical expression and no marker or structure is
 * crossed. Mixed styles and marker-intersecting expressions are left alone.
 */
final class TemplateStructureNormalizer
{
    private const SCOPES = ['text:p', 'text:h', 'text:list-item', 'table:table-cell', 'table:covered-table-cell', 'text:section', 'draw:text-box'];

    public function __construct(private readonly TemplateExpressionProjector $projector = new TemplateExpressionProjector()) {}

    public function normalize(DOMDocument $dom): TemplateStructureNormalizationResult
    {
        $diagnostics = [];
        $this->projector->project($dom, $diagnostics);
        $repairs = [];
        $skipped = [];
        $this->walk($dom, $repairs, $skipped);
        return new TemplateStructureNormalizationResult($repairs, $skipped, $diagnostics);
    }

    /** @param list<array<string, mixed>> $repairs @param list<array<string, mixed>> $skipped */
    private function walk(DOMNode $node, array &$repairs, array &$skipped): void
    {
        if ($node instanceof DOMElement && in_array($node->nodeName, self::SCOPES, true)) {
            $this->normalizeScope($node, $repairs, $skipped);
        }
        foreach (iterator_to_array($node->childNodes) as $child) $this->walk($child, $repairs, $skipped);
    }

    /** @param list<array<string, mixed>> $repairs @param list<array<string, mixed>> $skipped */
    private function normalizeScope(DOMElement $scope, array &$repairs, array &$skipped): void
    {
        $children = iterator_to_array($scope->childNodes);
        $index = 0;
        while ($index < count($children)) {
            $segments = [];
            while ($index < count($children)) {
                $segment = $this->segment($children[$index]);
                if ($segment === null) break;
                $segments[] = [$children[$index], $segment['text'], $segment['style'], $segment['type']];
                $index++;
            }
            $this->repairRun($segments, $repairs, $skipped);
            $index++;
        }
    }

    /** @return array{text: string, style: ?string, type: string}|null */
    private function segment(DOMNode $node): ?array
    {
        if ($node->nodeType === XML_TEXT_NODE) return ['text' => $node->nodeValue ?? '', 'style' => null, 'type' => 'text'];
        if (!$node instanceof DOMElement || $node->nodeName !== 'text:span') return null;
        foreach ($node->childNodes as $child) if ($child->nodeType !== XML_TEXT_NODE) return null;
        return ['text' => $node->textContent, 'style' => $node->getAttribute('text:style-name') ?: null, 'type' => 'span'];
    }

    /** @param list<array{0:DOMNode,1:string,2:?string,3:string}> $segments @param list<array<string, mixed>> $repairs @param list<array<string, mixed>> $skipped */
    private function repairRun(array $segments, array &$repairs, array &$skipped): void
    {
        if (count($segments) < 2) return;
        $logical = implode('', array_column($segments, 1));
        preg_match_all('/{{[^{}]*}}/', $logical, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$expression, $start]) {
            $end = $start + strlen($expression);
            $first = $last = null;
            $offset = 0;
            foreach ($segments as $position => $segment) {
                $segmentStart = $offset;
                $segmentEnd = $offset + strlen($segment[1]);
                if ($start === $segmentStart) $first = $position;
                if ($end === $segmentEnd) $last = $position;
                $offset = $segmentEnd;
            }
            if ($first === null || $last === null || $last <= $first) continue;
            $selected = array_slice($segments, $first, $last - $first + 1);
            $types = array_unique(array_column($selected, 3));
            $styles = array_unique(array_column($selected, 2));
            if (count($types) !== 1 || count($styles) !== 1) {
                $skipped[] = ['expression' => $expression, 'reason' => 'style_or_node_kind_conflict', 'fragment_count' => count($selected), 'style_names' => array_values($styles)];
                continue;
            }
            $firstNode = $selected[0][0];
            $this->setText($firstNode, $expression);
            foreach (array_slice($selected, 1) as [$node]) if ($node->parentNode !== null) $node->parentNode->removeChild($node);
            $repairs[] = ['expression' => $expression, 'repair_type' => 'merge_same_style_fragments', 'previous_fragment_count' => count($selected), 'resulting_fragment_count' => 1, 'preserved_style' => $selected[0][2]];
            return;
        }
    }

    private function setText(DOMNode $node, string $text): void
    {
        if ($node->nodeType === XML_TEXT_NODE) { $node->nodeValue = $text; return; }
        foreach ($node->childNodes as $child) if ($child->nodeType === XML_TEXT_NODE) { $child->nodeValue = $text; return; }
    }
}
