<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Support;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * EGN (ЕГН), the Bulgarian unified civil number.
 *
 * Layout: YY MM DD NNN C
 *  - YYMMDD is the birth date; the month carries the century:
 *    01..12 = 1900-1999, 21..32 = 1800-1899, 41..52 = 2000-2099.
 *  - NNN is the region/serial; the last of these three digits is even for a man
 *    and odd for a woman.
 *  - C is the check digit: sum of digits x (2,4,8,5,10,9,7,3,6) mod 11, where 10 becomes 0.
 */
final class Egn
{
    public const MALE = 'male';

    public const FEMALE = 'female';

    private const WEIGHTS = [2, 4, 8, 5, 10, 9, 7, 3, 6];

    public static function isValid(mixed $value): bool
    {
        $egn = Digits::clean($value);

        if (! Digits::only($egn, 10)) {
            return false;
        }

        if (self::checkDigit($egn) !== (int) $egn[9]) {
            return false;
        }

        return self::decodeDate($egn) !== null;
    }

    /**
     * The check digit for the first nine digits of an EGN.
     */
    public static function checkDigit(string $firstNine): int
    {
        $remainder = Digits::weightedSum($firstNine, self::WEIGHTS) % 11;

        return $remainder === 10 ? 0 : $remainder;
    }

    /**
     * Birth date encoded in a valid EGN, or null when the EGN is not valid.
     */
    public static function birthDate(mixed $value): ?DateTimeImmutable
    {
        return self::isValid($value) ? self::decodeDate(Digits::clean($value)) : null;
    }

    /**
     * "male" or "female" for a valid EGN, null otherwise.
     */
    public static function sex(mixed $value): ?string
    {
        if (! self::isValid($value)) {
            return null;
        }

        return ((int) Digits::clean($value)[8]) % 2 === 0 ? self::MALE : self::FEMALE;
    }

    /**
     * Age in full years on $on (default: today), or null for an invalid EGN.
     */
    public static function age(mixed $value, ?DateTimeInterface $on = null): ?int
    {
        $birth = self::birthDate($value);

        if ($birth === null) {
            return null;
        }

        $on = $on ? DateTimeImmutable::createFromInterface($on) : new DateTimeImmutable('today');

        return $birth > $on ? null : $birth->diff($on)->y;
    }

    /**
     * Builds a synthetic, checksum-valid EGN for tests and seeders.
     *
     * $serial (0..499) picks the region/serial block; the sex digit is derived from $sex.
     * Numbers made this way are arithmetic only and are not looked up anywhere.
     */
    public static function generate(DateTimeInterface $birthDate, string $sex = self::MALE, int $serial = 0): string
    {
        if (! in_array($sex, [self::MALE, self::FEMALE], true)) {
            throw new InvalidArgumentException('Sex must be "male" or "female".');
        }

        if ($serial < 0 || $serial > 499) {
            throw new InvalidArgumentException('Serial must be between 0 and 499.');
        }

        $year = (int) $birthDate->format('Y');
        $month = (int) $birthDate->format('n');

        $month += match (true) {
            $year >= 1800 && $year <= 1899 => 20,
            $year >= 1900 && $year <= 1999 => 0,
            $year >= 2000 && $year <= 2099 => 40,
            default => throw new InvalidArgumentException('An EGN can encode years 1800 to 2099 only.'),
        };

        $nnn = $serial * 2 + ($sex === self::FEMALE ? 1 : 0);

        $firstNine = sprintf('%02d%02d%02d%03d', $year % 100, $month, (int) $birthDate->format('j'), $nnn);

        return $firstNine.self::checkDigit($firstNine);
    }

    private static function decodeDate(string $egn): ?DateTimeImmutable
    {
        $yy = (int) substr($egn, 0, 2);
        $mm = (int) substr($egn, 2, 2);
        $dd = (int) substr($egn, 4, 2);

        [$century, $month] = match (true) {
            $mm >= 1 && $mm <= 12 => [1900, $mm],
            $mm >= 21 && $mm <= 32 => [1800, $mm - 20],
            $mm >= 41 && $mm <= 52 => [2000, $mm - 40],
            default => [null, null],
        };

        if ($century === null || ! checkdate($month, $dd, $century + $yy)) {
            return null;
        }

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $century + $yy, $month, $dd));
    }
}
