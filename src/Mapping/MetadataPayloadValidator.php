<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Validates concrete values for the bounded Phase-E metadata target set. */
final class MetadataPayloadValidator
{
    public function isCompatible(string $payloadKind, mixed $value): bool
    {
        return match ($payloadKind) {
            'STRING' => is_string($value),
            'LIST<STRING>' => $this->isStringList($value),
            'DATETIME' => is_string($value) && $this->isDateTime($value),
            'LANGUAGE' => is_string($value) && $this->isLanguage($value),
            'NON_NEGATIVE_INTEGER' => $this->isNonNegativeInteger($value),
            'DURATION' => is_string($value) && $this->isDuration($value),
            default => false,
        };
    }

    private function isStringList(mixed $value): bool
    {
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!is_string($item)) {
                return false;
            }
        }

        return true;
    }

    private function isNonNegativeInteger(mixed $value): bool
    {
        if (is_int($value)) {
            return $value >= 0;
        }

        return is_string($value) && preg_match('/^\+?[0-9]+$/D', $value) === 1;
    }

    private function isLanguage(string $value): bool
    {
        return preg_match('/^[A-Za-z]{1,8}(?:-[A-Za-z0-9]{1,8})*$/D', $value) === 1;
    }

    private function isDateTime(string $value): bool
    {
        $pattern = '/^(-?)([0-9]{4,})-(0[1-9]|1[0-2])-([0-9]{2})'
            . 'T([0-9]{2}):([0-9]{2}):([0-9]{2})(?:\.([0-9]+))?'
            . '(Z|([+-])([0-9]{2}):([0-9]{2}))?$/D';
        if (preg_match($pattern, $value, $matches) !== 1) {
            return false;
        }

        $year = $matches[2];
        if (preg_match('/^0+$/D', $year) === 1) {
            return false;
        }

        $month = (int) $matches[3];
        $day = (int) $matches[4];
        $daysInMonth = [31, $this->isLeapYear($year) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        if ($day < 1 || $day > $daysInMonth[$month - 1]) {
            return false;
        }

        $hour = (int) $matches[5];
        $minute = (int) $matches[6];
        $second = (int) $matches[7];
        $fraction = $matches[8] ?? '';
        if ($minute > 59 || $second > 60 || $hour > 24) {
            return false;
        }
        if ($hour === 24 && ($minute !== 0 || $second !== 0 || trim($fraction, '0') !== '')) {
            return false;
        }

        if (($matches[9] ?? '') !== '' && $matches[9] !== 'Z') {
            $offsetHour = (int) $matches[11];
            $offsetMinute = (int) $matches[12];
            if ($offsetMinute > 59 || $offsetHour > 14 || ($offsetHour === 14 && $offsetMinute !== 0)) {
                return false;
            }
        }

        return true;
    }

    private function isLeapYear(string $year): bool
    {
        return $this->decimalStringModulo($year, 4) === 0
            && ($this->decimalStringModulo($year, 100) !== 0 || $this->decimalStringModulo($year, 400) === 0);
    }

    private function decimalStringModulo(string $number, int $divisor): int
    {
        $remainder = 0;
        foreach (str_split($number) as $digit) {
            $remainder = (($remainder * 10) + (int) $digit) % $divisor;
        }

        return $remainder;
    }

    private function isDuration(string $value): bool
    {
        $pattern = '/^-?P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)D)?'
            . '(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?)?$/D';
        if (preg_match($pattern, $value, $matches) !== 1) {
            return false;
        }

        $datePartPresent = ($matches[1] ?? '') !== '' || ($matches[2] ?? '') !== '' || ($matches[3] ?? '') !== '';
        $timePartPresent = ($matches[4] ?? '') !== '' || ($matches[5] ?? '') !== '' || ($matches[6] ?? '') !== '';

        return ($datePartPresent || $timePartPresent) && (!str_contains($value, 'T') || $timePartPresent);
    }
}
