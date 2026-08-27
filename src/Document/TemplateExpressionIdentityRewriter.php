<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use DOMElement;
use DOMNode;

/**
 * Rewrites the variable identity of the bounded template syntax inside a
 * detached native subtree while retaining its text-node fragmentation.
 */
final class TemplateExpressionIdentityRewriter
{
    private const BOUNDARIES = [
        'text:p',
        'text:h',
        'text:list-item',
        'table:table-cell',
        'table:covered-table-cell',
        'text:section',
        'draw:text-box',
    ];

    /**
     * @throws SectionCloneException when a token is outside the supported
     *                               template grammar.
     */
    public function rewrite(DOMElement $root, int $index, string $sectionName): void
    {
        /** @var array<int, list<DOMNode>> $groups */
        $groups = [];
        $this->collectTextGroups($root, $root, $groups);

        foreach ($groups as $nodes) {
            $this->rewriteGroup($nodes, $index, $sectionName);
        }
    }

    /** @param array<int, list<DOMNode>> $groups */
    private function collectTextGroups(DOMNode $node, DOMElement $root, array &$groups): void
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $scope = $this->scope($node, $root);
            $groups[spl_object_id($scope)][] = $node;
            return;
        }

        foreach ($node->childNodes as $child) {
            $this->collectTextGroups($child, $root, $groups);
        }
    }

    private function scope(DOMNode $node, DOMElement $root): DOMNode
    {
        for ($current = $node->parentNode; $current !== null; $current = $current->parentNode) {
            if ($current === $root || in_array($current->nodeName, self::BOUNDARIES, true)) {
                return $current;
            }
        }

        return $root;
    }

    /** @param list<DOMNode> $nodes */
    private function rewriteGroup(array $nodes, int $index, string $sectionName): void
    {
        $text = '';
        $offsets = [];
        foreach ($nodes as $node) {
            $start = strlen($text);
            $text .= $node->nodeValue ?? '';
            $offsets[] = [$node, $start, strlen($text)];
        }

        if (!str_contains($text, '{{')) {
            return;
        }

        preg_match_all('/\{\{([^}]*)\}\}/', $text, $matches, PREG_OFFSET_CAPTURE);
        foreach (array_reverse($matches[0]) as $match) {
            [$token, $tokenStart] = $match;
            $body = substr($token, 2, -2);
            $rewrittenBody = $this->rewriteBody($body, $index);
            if ($rewrittenBody === null) {
                throw new SectionCloneException(
                    $sectionName,
                    sprintf('unsupported template expression "%s" inside clone', $token)
                );
            }
            $endOffset = $tokenStart + strlen($token);
            [$node, $nodeStart, $nodeEnd] = $this->offsetFor($offsets, $endOffset - 2);
            $localOffset = ($endOffset - 2) - $nodeStart;
            $node->nodeValue = substr($node->nodeValue ?? '', 0, $localOffset)
                . '_' . $index
                . substr($node->nodeValue ?? '', $localOffset);
        }
    }

    /** @param list<array{DOMNode, int, int}> $offsets */
    private function offsetFor(array $offsets, int $offset): array
    {
        foreach ($offsets as [$node, $start, $end]) {
            if ($offset >= $start && $offset < $end) {
                return [$node, $start, $end];
            }
        }

        $last = $offsets[count($offsets) - 1];
        return [$last[0], $last[1], $last[2]];
    }

    private function rewriteBody(string $body, int $index): ?string
    {
        if (preg_match('/^(\w+)$/', $body, $match)) {
            return $match[1] . '_' . $index;
        }

        if (preg_match('/^(\w+):(\w+)(\|[^}]*)?$/', $body, $match)) {
            return $match[1] . ':' . $match[2] . '_' . $index . ($match[3] ?? '');
        }

        if (preg_match('/^#(if|ifnot|elseif|foreach):(\w+)$/', $body, $match)) {
            return '#' . $match[1] . ':' . $match[2] . '_' . $index;
        }

        if (in_array($body, ['#else', '#endif', '#endforeach'], true)) {
            return $body;
        }

        return null;
    }
}
