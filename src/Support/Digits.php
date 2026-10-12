<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Support;

/**
 * Small helpers shared by the checksum classes.
 *
 * @internal
 */
final class Digits
{
    /**
     * Turns user input into a compact string: trims it and removes spaces,
     * dashes, dots and slashes that people type between digit groups.
     */
    public static function clean(mixed $value): ?string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        return preg_replace('/[\s\-\.\/\x{00A0}]+/u', '', trim($value));
    }

    public static function only(?string $value, int ...$lengths): bool
    {
        if ($value === null || ! ctype_digit($value)) {
            return false;
        }

        return $lengths === [] || in_array(strlen($value), $lengths, true);
    }

    /**
     * Weighted sum of the first count($weights) digits.
     *
     * @param  list<int>  $weights
     */
    public static function weightedSum(string $digits, array $weights, int $offset = 0): int
    {
        $sum = 0;

        foreach ($weights as $i => $weight) {
            $sum += ((int) $digits[$offset + $i]) * $weight;
        }

        return $sum;
    }
}
