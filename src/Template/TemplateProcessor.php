<?php

namespace OdtTemplateEngine\Template;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Stateless template-language operations for supplied ODT DOM regions.
 *
 * Package state, document context, assignment state, and structured ODT
 * element insertion remain outside this class.
 */
final class TemplateProcessor
{
    /**
     * Normalize placeholder fragments split across paragraph children.
     */
    public function normalizeTemplateDom(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $paragraphs = $xpath->query('//text:p');

        foreach ($paragraphs as $paragraph) {
            if (!$paragraph instanceof DOMElement) {
                continue;
            }

            $buffer = '';
            $nodesToRemove = [];

            foreach (iterator_to_array($paragraph->childNodes) as $child) {
                if ($child->nodeType !== XML_TEXT_NODE && $child->nodeName !== 'text:span') {
                    continue;
                }

                $buffer .= $child->textContent;
                $nodesToRemove[] = $child;

                if (substr_count($buffer, '{{') > 0
                    && substr_count($buffer, '{{') === substr_count($buffer, '}}')
                ) {
                    foreach ($nodesToRemove as $node) {
                        $paragraph->removeChild($node);
                    }

                    $paragraph->appendChild($dom->createTextNode($buffer));
                    $buffer = '';
                    $nodesToRemove = [];
                }
            }
        }
    }

    /**
     * Repair placeholder fragments split across nested text spans.
     */
    public function fixBrokenVariables(DOMNode $node): void
    {
        if (!$node->hasChildNodes()) {
            return;
        }

        $children = iterator_to_array($node->childNodes);
        $buffer = '';
        $inPlaceholder = false;
        $nodesToRemove = [];

        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->nodeValue;

                if ($inPlaceholder) {
                    $buffer .= $text;
                    $nodesToRemove[] = $child;

                    if (str_contains($text, '}}')) {
                        $firstNode = array_shift($nodesToRemove);
                        $firstNode->nodeValue = $buffer;
                        foreach ($nodesToRemove as $remove) {
                            if ($remove->parentNode) {
                                $remove->parentNode->removeChild($remove);
                            }
                        }
                        $buffer = '';
                        $nodesToRemove = [];
                        $inPlaceholder = false;
                    }
                } elseif (preg_match('/{{[^}]*$/', $text)) {
                    $inPlaceholder = true;
                    $buffer = $text;
                    $nodesToRemove[] = $child;
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'text:span') {
                $this->fixBrokenVariables($child);
            }
        }
    }

    /**
     * Replace plain and scalar-filter placeholders in one text value.
     *
     * The filter callback remains supplied by the facade so protected
     * `applyFilter()` overrides continue to participate in rendering.
     *
     * @param array<string, mixed> $values
     * @param callable(string, mixed, ?string): string $applyFilter
     */
    public function replaceScalarText(string $text, array $values, callable $applyFilter): string
    {
        foreach ($values as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }

        return preg_replace_callback(
            '/{{(\w+):(\w+)(?:\|([^}]+))?}}/',
            function (array $matches) use ($values, $applyFilter): string {
                $filter = $matches[1];
                $key = $matches[2];
                $option = $matches[3] ?? null;
                $value = $values[$key] ?? '';

                return $applyFilter($filter, $value, $option);
            },
            $text
        ) ?? $text;
    }

    /**
     * Apply the existing ordinary scalar filter behavior.
     */
    public function applyFilter(string $filter, string $value, ?string $option = null): string
    {
        return match ($filter) {
            'upper' => mb_strtoupper($value),
            'lower' => mb_strtolower($value),
            'trim' => trim($value),
            'nl2br' => $value,
            'ul' => $value,
            'date' => date($option ?: 'd.m.Y', strtotime($value)),
            'number' => number_format((float) str_replace(',', '.', $value), (int) ($option ?? 2), ',', '.'),
            default => $value,
            'checkbox' => ($value) ? '☑' : '☐',
            'currency' => number_format((float) str_replace(',', '.', $value), 2, ',', '.') . ' €',
        };
    }

    /**
     * Replace nl2br placeholders with text and ODT line-break nodes.
     *
     * @param array<string, string> $values
     */
    public function replaceNl2brInDom(DOMDocument $dom, array $values): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $nodes = $xpath->query('//text()');

        foreach ($nodes as $textNode) {
            $text = $textNode->nodeValue;

            if (preg_match('/{{nl2br:(\w+)}}/', $text, $match)) {
                $key = $match[1];
                $original = $values[$key] ?? '';
                $parts = preg_split('/\r\n|\n|\r/', $original);
                $parent = $textNode->parentNode;

                $newNodes = [];
                foreach ($parts as $i => $part) {
                    if ($i > 0) {
                        $newNodes[] = $dom->createElement('text:line-break');
                    }
                    if ($part !== '') {
                        $newNodes[] = $dom->createTextNode($part);
                    }
                }

                foreach ($newNodes as $newNode) {
                    $parent->insertBefore($newNode, $textNode);
                }

                $parent->removeChild($textNode);
            }
        }
    }

    /**
     * Replace unordered and ordered list placeholders with ODT lists.
     *
     * @param array<string, string> $values
     */
    public function replaceListsInDom(DOMDocument $dom, array $values): void
    {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//text()');

        foreach ($nodes as $textNode) {
            $text = $textNode->nodeValue;

            if (preg_match('/{{(ul|ol):(\w+)}}/', $text, $match)) {
                $listType = $match[1];
                $key = $match[2];
                $original = $values[$key] ?? '';
                $lines = preg_split('/\r\n|\r|\n/', $original);

                $list = $dom->createElement('text:list');
                $styleName = ($listType === 'ol') ? 'Numbering_20_Symbol' : 'Bullet_20_Symbol';
                $list->setAttribute('text:style-name', $styleName);

                foreach ($lines as $line) {
                    $listItem = $dom->createElement('text:list-item');
                    $paragraph = $dom->createElement('text:p');
                    $paragraph->appendChild($dom->createTextNode($line));
                    $listItem->appendChild($paragraph);
                    $list->appendChild($listItem);
                }

                $paragraphNode = $textNode->parentNode;
                $paragraphParent = $paragraphNode->parentNode;

                if ($paragraphParent && $paragraphNode->nodeName === 'text:p') {
                    $paragraphParent->replaceChild($list, $paragraphNode);
                }
            }
        }
    }

    /**
     * Apply paragraph-based conditional blocks to a supplied ODT DOM.
     *
     * @param array<string, mixed> $values
     * @param callable(string, array<string, mixed>): bool $evaluateCondition
     */
    public function applyConditionalsInDom(
        DOMDocument $dom,
        array $values,
        callable $evaluateCondition
    ): void {
        $xpath = new DOMXPath($dom);
        $paragraphs = iterator_to_array($xpath->query('//text:p'));
        $i = 0;

        while ($i < count($paragraphs)) {
            $node = $paragraphs[$i];
            $text = trim($node->textContent);

            if (preg_match('/{{#(ifnot|if):(.+?)}}/', $text, $match)) {
                $type = $match[1];
                $expr = trim($match[2]);

                $conditions = [
                    ['start' => $i, 'expr' => $expr, 'type' => $type],
                ];

                $else = null;
                $end = null;
                $j = $i + 1;

                while ($j < count($paragraphs)) {
                    $inner = trim($paragraphs[$j]->textContent);
                    if (preg_match('/{{#elseif:(.+?)}}/', $inner, $m)) {
                        $conditions[] = [
                            'start' => $j,
                            'expr' => trim($m[1]),
                            'type' => 'if',
                        ];
                    } elseif ($inner === '{{#else}}') {
                        $else = $j;
                    } elseif ($inner === '{{#endif}}') {
                        $end = $j;
                        break;
                    }
                    $j++;
                }

                if ($end === null) {
                    $i++;
                    continue;
                }

                $keepStart = null;
                $keepEnd = null;

                for ($c = 0; $c < count($conditions); $c++) {
                    $condition = $conditions[$c];
                    $result = $evaluateCondition($condition['expr'], $values);
                    if ($condition['type'] === 'ifnot') {
                        $result = !$result;
                    }

                    if ($result) {
                        $keepStart = $condition['start'] + 1;
                        $keepEnd = isset($conditions[$c + 1])
                            ? $conditions[$c + 1]['start'] - 1
                            : ($else ?? $end) - 1;
                        break;
                    }
                }

                if ($keepStart === null && $else !== null) {
                    $keepStart = $else + 1;
                    $keepEnd = $end - 1;
                }

                for ($k = $end; $k >= $i; $k--) {
                    if ($k >= $keepStart && $k <= $keepEnd) {
                        continue;
                    }

                    $paragraph = $paragraphs[$k];
                    if ($paragraph->parentNode) {
                        $paragraph->parentNode->removeChild($paragraph);
                    }
                }

                $paragraphs = iterator_to_array($xpath->query('//text:p'));
                $i = $i;
            } else {
                $i++;
            }
        }
    }

    /**
     * Evaluate the existing simple condition expression semantics.
     *
     * @param array<string, mixed> $values
     */
    public function evaluateCondition(string $expression, array $values): bool
    {
        if (preg_match('/^(\w+)\s*(==|!=|>=|<=|>|<)\s*(.+)$/', $expression, $match)) {
            $variable = $match[1];
            $operator = $match[2];
            $value = trim($match[3], '"\'');

            $left = $values[$variable] ?? null;
            if (is_numeric($left) && is_numeric($value)) {
                $left = (float) $left;
                $value = (float) $value;
            }

            return match ($operator) {
                '==' => $left == $value,
                '!=' => $left != $value,
                '>' => $left > $value,
                '<' => $left < $value,
                '>=' => $left >= $value,
                '<=' => $left <= $value,
            };
        }

        $value = $values[$expression] ?? false;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
