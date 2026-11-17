<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use InvalidArgumentException;

/** @internal Maps the bounded SA-02 Section style vocabulary to ODF. */
final class SectionStyleOptions
{
    /** @return array<string, array<string, mixed>> */
    public static function propertyGroups(array $options): array
    {
        $allowed = ['margin-left', 'margin-right', 'background-color', 'columns'];
        foreach ($options as $key => $_value) {
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException(sprintf('Unsupported Section style option "%s".', (string) $key));
            }
        }

        $properties = [];
        foreach (['margin-left', 'margin-right'] as $key) {
            if (array_key_exists($key, $options)) {
                $properties['fo:' . $key] = self::odfValue($options[$key], $key, true);
            }
        }
        if (array_key_exists('background-color', $options)) {
            $properties['fo:background-color'] = self::color($options['background-color'], 'background-color');
        }

        if (array_key_exists('columns', $options)) {
            if (!is_array($options['columns'])) {
                throw new InvalidArgumentException('Section style "columns" must be an array.');
            }
            $columnProperties = self::columns($options['columns']);
            if (isset($columnProperties['text:dont-balance-text-columns'])) {
                $properties['text:dont-balance-text-columns'] = $columnProperties['text:dont-balance-text-columns'];
                unset($columnProperties['text:dont-balance-text-columns']);
            }
            $properties['style:columns'] = $columnProperties;
        }

        return $properties === [] ? [] : ['style:section-properties' => $properties];
    }

    /** @return array<string, mixed> */
    private static function columns(array $columns): array
    {
        $allowed = ['count', 'gap', 'relative-widths', 'balance', 'separator', 'column-indents'];
        foreach ($columns as $key => $_value) {
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException(sprintf('Unsupported Section columns option "%s".', (string) $key));
            }
        }
        if (!array_key_exists('count', $columns) || !is_int($columns['count']) || $columns['count'] < 1) {
            throw new InvalidArgumentException('Section columns require a positive integer "count".');
        }
        $count = $columns['count'];
        if ($count > 16383) {
            throw new InvalidArgumentException('Section columns count must not exceed 16383.');
        }
        $result = ['fo:column-count' => (string) $count];
        $hasGap = array_key_exists('gap', $columns);
        $hasIndents = array_key_exists('column-indents', $columns);
        if ($hasGap && $hasIndents) {
            throw new InvalidArgumentException('Section columns "gap" and "column-indents" are mutually exclusive.');
        }
        if ($hasGap) {
            $gap = self::odfValue($columns['gap'], 'column gap', false);
            if (isset($columns['relative-widths']) && self::hasUnequalWeights($columns['relative-widths'], $count)) {
                $result['style:column'] = self::gapIndents($gap, $count);
            } else {
                $result['fo:column-gap'] = $gap;
            }
        } elseif ($hasIndents) {
            $result['style:column'] = self::explicitIndents($columns['column-indents'], $count);
        }
        if (array_key_exists('balance', $columns)) {
            if (!is_bool($columns['balance'])) {
                throw new InvalidArgumentException('Section columns "balance" must be boolean.');
            }
            // ODF names the inverse of the public semantic option.
            $result['text:dont-balance-text-columns'] = $columns['balance'] ? 'false' : 'true';
        }

        if (array_key_exists('relative-widths', $columns)) {
            $widths = $columns['relative-widths'];
            if (!is_array($widths) || count($widths) !== $count || array_values($widths) !== $widths) {
                throw new InvalidArgumentException('Section relative-widths must contain one positive integer per column.');
            }
            foreach ($widths as $width) {
                if (!is_int($width) || $width < 1) {
                    throw new InvalidArgumentException('Section relative-widths must contain positive integers.');
                }
            }
            $normalized = RelativeColumnWidthNormalizer::normalize($widths);
            $indented = $result['style:column'] ?? [];
            $result['style:column'] = [];
            foreach ($normalized as $index => $width) {
                $result['style:column'][] = ($indented[$index] ?? []) + ['style:rel-width' => $width];
            }
            if (!$hasGap && !$hasIndents && self::hasUnequalWeights($widths, $count)) {
                foreach ($result['style:column'] as $index => $column) {
                    $result['style:column'][$index] = [
                        'fo:start-indent' => '0cm',
                        'fo:end-indent' => '0cm',
                    ] + $column;
                }
            }
        }

        if ($hasIndents && !array_key_exists('relative-widths', $columns)) {
            $normalized = RelativeColumnWidthNormalizer::normalize(array_fill(0, $count, 1));
            foreach ($result['style:column'] as $index => $column) {
                $result['style:column'][$index] = $column + ['style:rel-width' => $normalized[$index]];
            }
        }

        if (array_key_exists('separator', $columns)) {
            if ($count < 2 || !is_array($columns['separator'])) {
                throw new InvalidArgumentException('Section separators require at least two columns and an array.');
            }
            $separator = $columns['separator'];
            $separatorAllowed = ['style', 'width', 'color', 'height'];
            foreach ($separator as $key => $_value) {
                if (!in_array($key, $separatorAllowed, true)) {
                    throw new InvalidArgumentException(sprintf('Unsupported Section separator option "%s".', (string) $key));
                }
            }
            if (!array_key_exists('style', $separator)) {
                throw new InvalidArgumentException('Section separators require a "style".');
            }
            $separatorProperties = ['style:style' => self::separatorStyle($separator['style'])];
            if (array_key_exists('width', $separator)) {
                $separatorProperties['style:width'] = self::odfValue($separator['width'], 'separator width', false);
            }
            if (array_key_exists('height', $separator)) {
                $separatorProperties['style:height'] = self::percentage($separator['height'], 'separator height');
            }
            if (array_key_exists('color', $separator)) {
                $separatorProperties['style:color'] = self::hexColor($separator['color'], 'separator color');
            }
            // ODF requires column-sep before the column children.
            $columnsBefore = $result['style:column'] ?? null;
            unset($result['style:column']);
            $result['style:column-sep'] = $separatorProperties;
            if ($columnsBefore !== null) {
                $result['style:column'] = $columnsBefore;
            }
        }

        return $result;
    }

    private static function hasUnequalWeights(mixed $weights, int $count): bool
    {
        return is_array($weights)
            && count($weights) === $count
            && array_values($weights) === $weights
            && count(array_filter($weights, static fn (mixed $weight): bool => is_int($weight))) === $count
            && count(array_unique($weights, SORT_REGULAR)) > 1;
    }

    /** @return list<array<string, string>> */
    private static function gapIndents(string $gap, int $count): array
    {
        [$number, $unit] = self::lengthParts($gap, 'column gap');
        $halfNumber = self::formatNumber($number / 2);
        if ($number > 0.0 && $halfNumber === '0') {
            throw new InvalidArgumentException('Section column gap is too small to split without rounding to zero.');
        }
        $half = $halfNumber . $unit;
        $columns = [];
        for ($index = 0; $index < $count; $index++) {
            $columns[] = [
                'fo:start-indent' => $index === 0 ? '0cm' : $half,
                'fo:end-indent' => $index === $count - 1 ? '0cm' : $half,
            ];
        }
        return $columns;
    }

    /** @return list<array<string, string>> */
    private static function explicitIndents(mixed $indents, int $count): array
    {
        if (!is_array($indents) || count($indents) !== $count || array_values($indents) !== $indents) {
            throw new InvalidArgumentException('Section column-indents must contain one array per column.');
        }
        return array_map(static function (mixed $indent): array {
            if (!is_array($indent)) {
                throw new InvalidArgumentException('Each Section column indent must be an array with start and end.');
            }
            $keys = array_keys($indent);
            if (array_diff($keys, ['start', 'end']) !== [] || !array_key_exists('start', $indent) || !array_key_exists('end', $indent)) {
                throw new InvalidArgumentException('Each Section column indent requires only start and end.');
            }
            return [
                'fo:start-indent' => self::odfValue($indent['start'], 'column start indent', false),
                'fo:end-indent' => self::odfValue($indent['end'], 'column end indent', false),
            ];
        }, $indents);
    }

    /** @return array{0: float, 1: string} */
    private static function lengthParts(string $value, string $label): array
    {
        if (!preg_match('/^((?:\d+(?:\.\d+)?|\.\d+))(cm|mm|in|pt|pc|px)$/', $value, $matches)) {
            throw new InvalidArgumentException(sprintf('Section %s must be a non-negative ODF length.', $label));
        }
        $number = (float) $matches[1];
        if (!is_finite($number)) {
            throw new InvalidArgumentException(sprintf('Section %s must be a finite ODF length.', $label));
        }
        return [$number, $matches[2]];
    }

    private static function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.') ?: '0';
    }

    private static function odfValue(mixed $value, string $label, bool $allowPercentage): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Section %s must be a non-empty ODF value.', $label));
        }
        $value = trim($value);
        if ($value === '0') {
            return '0cm';
        }
        $unit = $allowPercentage ? '(?:cm|mm|in|pt|pc|px|%)' : '(?:cm|mm|in|pt|pc|px)';
        if (!preg_match('/^(?:0|(?:\d+(?:\.\d+)?|\.\d+)' . $unit . ')$/', $value)) {
            throw new InvalidArgumentException(sprintf('Section %s must be a non-negative ODF length%s.', $label, $allowPercentage ? ' or percentage' : ''));
        }
        if (preg_match('/^((?:\d+(?:\.\d+)?|\.\d+))(?:cm|mm|in|pt|pc|px|%)$/', $value, $matches)
            && !is_finite((float) $matches[1])) {
            throw new InvalidArgumentException(sprintf('Section %s must be a finite ODF length.', $label));
        }
        return $value;
    }

    private static function color(mixed $value, string $label): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Section %s must be a non-empty color.', $label));
        }
        $value = trim($value);
        if ($value !== 'transparent' && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            throw new InvalidArgumentException(sprintf('Section %s must be #RRGGBB or transparent.', $label));
        }
        return $value;
    }

    private static function hexColor(mixed $value, string $label): string
    {
        if (!is_string($value) || !preg_match('/^#[0-9a-fA-F]{6}$/', trim($value))) {
            throw new InvalidArgumentException(sprintf('Section %s must be #RRGGBB.', $label));
        }
        return trim($value);
    }

    private static function percentage(mixed $value, string $label): string
    {
        if (!is_string($value) || !preg_match('/^(?:\d+(?:\.\d+)?|\.\d+)%$/', trim($value))) {
            throw new InvalidArgumentException(sprintf('Section %s must be a percentage.', $label));
        }
        $number = (float) rtrim(trim($value), '%');
        if ($number < 0 || $number > 100) {
            throw new InvalidArgumentException(sprintf('Section %s must be between 0%% and 100%%.', $label));
        }
        return trim($value);
    }

    private static function separatorStyle(mixed $value): string
    {
        if (!is_string($value) || !in_array($value, ['none', 'solid', 'dashed', 'dotted', 'double'], true)) {
            throw new InvalidArgumentException('Section separator style must be none, solid, dashed, dotted, or double.');
        }
        return $value;
    }
}
