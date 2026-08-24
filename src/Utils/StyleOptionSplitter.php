<?php

namespace OdtTemplateEngine\Utils;

/**
 * Classifies mixed convenience style arrays by their ODF responsibility layer.
 *
 * The splitter preserves the historical convenience API while allowing callers
 * to persist cell, paragraph, and text properties on the correct ODF style
 * families.
 */
final class StyleOptionSplitter
{
    /**
     * @param array<string, mixed> $options
     * @param string $context Supported contexts: paragraph, table-cell.
     * @return array{cell: array<string, mixed>, paragraph: array<string, mixed>, text: array<string, mixed>}
     */
    public static function split(array $options, string $context = 'paragraph'): array
    {
        $result = [
            'cell' => [],
            'paragraph' => [],
            'text' => [],
        ];

        $textKeys = [
            'bold',
            'weight',
            'font-weight',
            'italic',
            'font-style',
            'underline',
            'text-decoration',
            'text-line-through',
            'color',
            'font-size',
            'font-family',
            'font-variant',
            'monospace',
            'style:text-position',
        ];

        $paragraphKeys = [
            'align',
            'text-align',
            'text-indent',
            'line-height',
            'margin',
            'margin-top',
            'margin-right',
            'margin-bottom',
            'margin-left',
            'padding',
            'padding-top',
            'padding-right',
            'padding-bottom',
            'padding-left',
            'border',
            'border-top',
            'border-right',
            'border-bottom',
            'border-left',
            'keep-with-next',
            'break-before',
            'break-after',
            'writing-mode',
            'number-lines',
            'line-number',
            'tab-stops',
        ];

        $cellKeys = [
            'background',
            'background-color',
            'padding',
            'padding-top',
            'padding-right',
            'padding-bottom',
            'padding-left',
            'border',
            'border-top',
            'border-right',
            'border-bottom',
            'border-left',
            '__column-width',
        ];

        foreach ($options as $key => $value) {
            $key = trim((string) $key);

            if ($context === 'table-cell' && in_array($key, $cellKeys, true)) {
                $result['cell'][$key] = $value;
                continue;
            }

            if (in_array($key, $textKeys, true)) {
                $normalizedKey = $key === 'weight' ? 'font-weight' : $key;
                $result['text'][$normalizedKey] = $value;
                continue;
            }

            if (in_array($key, $paragraphKeys, true)) {
                $normalizedKey = $key === 'align' ? 'text-align' : $key;
                $result['paragraph'][$normalizedKey] = $value;
                continue;
            }

            if (preg_match('/^(fo:|style:|draw:|svg:|loext:)/', $key)) {
                $target = $context === 'table-cell' ? 'cell' : 'paragraph';
                $result[$target][$key] = $value;
                continue;
            }

            // Preserve unknown semantic keys on the native context as an
            // advanced compatibility escape hatch.
            $target = $context === 'table-cell' ? 'cell' : 'paragraph';
            $result[$target][$key] = $value;
        }

        return $result;
    }
}
