<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Support;

use InvalidArgumentException;

/**
 * EIK / BULSTAT (ЕИК / БУЛСТАТ), the Bulgarian company and organisation identifier.
 *
 * 9 digits: check digit = sum of digits 1-8 x (1..8) mod 11; if that is 10,
 *           use weights (3..10) instead; if it is 10 again, the digit is 0.
 * 13 digits (branches): the first nine must be a valid 9-digit EIK; digit 13 checks
 *           digits 9-12 with weights (2,7,3,5), falling back to (4,9,5,7), then 0.
 */
final class Eik
{
    public static function isValid(mixed $value, ?int $length = null): bool
    {
        $eik = Digits::clean($value);

        if (! Digits::only($eik, ...($length ? [$length] : [9, 13]))) {
            return false;
        }

        if (self::checkDigit9($eik) !== (int) $eik[8]) {
            return false;
        }

        return strlen($eik) === 9 || self::checkDigit13($eik) === (int) $eik[12];
    }

    public static function checkDigit9(string $digits): int
    {
        $remainder = Digits::weightedSum($digits, [1, 2, 3, 4, 5, 6, 7, 8]) % 11;

        if ($remainder === 10) {
            $remainder = Digits::weightedSum($digits, [3, 4, 5, 6, 7, 8, 9, 10]) % 11;
        }

        return $remainder === 10 ? 0 : $remainder;
    }

    public static function checkDigit13(string $digits): int
    {
        $remainder = Digits::weightedSum($digits, [2, 7, 3, 5], 8) % 11;

        if ($remainder === 10) {
            $remainder = Digits::weightedSum($digits, [4, 9, 5, 7], 8) % 11;
        }

        return $remainder === 10 ? 0 : $remainder;
    }

    /**
     * Completes 8 digits (giving a 9-digit EIK) or 8 + 3 = 11 digits (base plus branch,
     * giving a 13-digit EIK) with the check digits. Intended for tests and seeders.
     */
    public static function generate(string $digits): string
    {
        if (Digits::only($digits, 8)) {
            return $digits.self::checkDigit9($digits);
        }

        if (Digits::only($digits, 11)) {
            $nine = substr($digits, 0, 8).self::checkDigit9($digits);
            $twelve = $nine.substr($digits, 8, 3);

            return $twelve.self::checkDigit13($twelve);
        }

        throw new InvalidArgumentException('Pass 8 digits (for a 9-digit EIK) or 11 digits (for a 13-digit EIK).');
    }
}
