<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Support;

use InvalidArgumentException;

/**
 * LNCh (ЛНЧ), the personal number of a foreigner living in Bulgaria.
 *
 * Ten digits; the last is a check digit: sum of the first nine digits x
 * (21,19,17,13,11,9,7,3,1) mod 10.
 */
final class Lnch
{
    private const WEIGHTS = [21, 19, 17, 13, 11, 9, 7, 3, 1];

    public static function isValid(mixed $value): bool
    {
        $lnch = Digits::clean($value);

        return Digits::only($lnch, 10) && self::checkDigit($lnch) === (int) $lnch[9];
    }

    public static function checkDigit(string $firstNine): int
    {
        return Digits::weightedSum($firstNine, self::WEIGHTS) % 10;
    }

    /**
     * Completes nine digits with the check digit (for tests and seeders).
     */
    public static function generate(string $firstNine): string
    {
        if (! Digits::only($firstNine, 9)) {
            throw new InvalidArgumentException('Pass exactly nine digits.');
        }

        return $firstNine.self::checkDigit($firstNine);
    }
}
