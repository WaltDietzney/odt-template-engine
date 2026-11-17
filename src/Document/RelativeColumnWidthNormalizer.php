<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Document;

use InvalidArgumentException;

/** @internal Stateless Writer-compatible normalizer for Section relative widths. */
final class RelativeColumnWidthNormalizer
{
    private const TOTAL = 65535;

    /** @param list<int> $weights @return list<string> */
    public static function normalize(array $weights): array
    {
        $gcd = 0;
        foreach ($weights as $weight) {
            $gcd = self::gcd($gcd, $weight);
        }

        $reduced = array_map(static fn (int $weight): int => intdiv($weight, $gcd), $weights);
        $sum = 0;
        foreach ($reduced as $weight) {
            if ($sum > PHP_INT_MAX - $weight) {
                throw new InvalidArgumentException('Section relative-widths are not representable as bounded ODF weights.');
            }
            $sum += $weight;
        }

        $floors = [];
        foreach ($reduced as $weight) {
            $floor = self::floorProduct(self::TOTAL, $weight, $sum);
            if ($floor < 1) {
                throw new InvalidArgumentException('Section relative-widths cannot be represented with a positive ideal weight for every column.');
            }
            $floors[] = $floor;
        }

        $result = [];
        $materialized = 0;
        $last = array_key_last($floors);
        foreach ($floors as $index => $floor) {
            $value = $index === $last ? self::TOTAL - $materialized : $floor;
            if ($value < 1) {
                throw new InvalidArgumentException('Section relative-widths cannot be represented by positive ODF weights.');
            }
            $materialized += $value;
            $result[] = $value . '*';
        }
        return $result;
    }

    private static function gcd(int $left, int $right): int
    {
        while ($right !== 0) {
            $remainder = $left % $right;
            $left = $right;
            $right = $remainder;
        }
        return $left;
    }

    /** Exact floor($factor * $numerator / $denominator), without overflow or float conversion. */
    private static function floorProduct(int $factor, int $numerator, int $denominator): int
    {
        $quotient = 0;
        $remainder = 0;
        $bits = decbin($factor);
        foreach (str_split($bits) as $bit) {
            // Double the current remainder without ever evaluating 2 * remainder.
            if ($remainder >= $denominator - $remainder) {
                $remainder = $remainder - ($denominator - $remainder);
                $quotient = ($quotient * 2) + 1;
            } else {
                $remainder += $remainder;
                $quotient *= 2;
            }
            if ($bit === '1') {
                // Add numerator modulo denominator without overflowing.
                if ($remainder >= $denominator - $numerator) {
                    $remainder = $remainder - ($denominator - $numerator);
                    ++$quotient;
                } else {
                    $remainder += $numerator;
                }
            }
        }
        return $quotient;
    }
}
