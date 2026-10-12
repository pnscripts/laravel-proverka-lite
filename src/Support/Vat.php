<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Support;

/**
 * Bulgarian VAT identification number (ИН по ЗДДС): "BG" followed by
 *  - a 9-digit EIK (companies),
 *  - a 10-digit EGN or LNCh (natural persons), or
 *  - a 10-digit number issued to other foreigners, checked with weights
 *    (4,3,2,7,6,5,4,3,2) and 11 - (sum mod 11).
 *
 * This is an offline format and checksum check; it does not ask VIES whether
 * the number is registered.
 */
final class Vat
{
    public const KIND_EIK = 'eik';

    public const KIND_EGN = 'egn';

    public const KIND_LNCH = 'lnch';

    public const KIND_OTHER = 'other';

    /**
     * Upper-cases the value and removes separators; returns null for non-strings.
     */
    public static function normalize(mixed $value): ?string
    {
        $clean = Digits::clean($value);

        return $clean === null ? null : strtoupper($clean);
    }

    /**
     * @param  list<string>|null  $kinds  limit to some of the KIND_* constants
     */
    public static function isValid(mixed $value, ?array $kinds = null, bool $prefixRequired = true): bool
    {
        $kind = self::kind($value, $prefixRequired);

        return $kind !== null && ($kinds === null || in_array($kind, $kinds, true));
    }

    /**
     * Which kind of number is behind a valid VAT number, or null when it is not valid.
     */
    public static function kind(mixed $value, bool $prefixRequired = true): ?string
    {
        $vat = self::normalize($value);

        if ($vat === null) {
            return null;
        }

        if (str_starts_with($vat, 'BG')) {
            $vat = substr($vat, 2);
        } elseif ($prefixRequired) {
            return null;
        }

        if (Digits::only($vat, 9)) {
            return Eik::isValid($vat, 9) ? self::KIND_EIK : null;
        }

        if (! Digits::only($vat, 10)) {
            return null;
        }

        return match (true) {
            Egn::isValid($vat) => self::KIND_EGN,
            Lnch::isValid($vat) => self::KIND_LNCH,
            self::isValidOther($vat) => self::KIND_OTHER,
            default => null,
        };
    }

    public static function fromEik(string $eik): ?string
    {
        $eik = Digits::clean($eik);

        return Eik::isValid($eik, 9) ? 'BG'.$eik : null;
    }

    private static function isValidOther(string $digits): bool
    {
        $check = (11 - Digits::weightedSum($digits, [4, 3, 2, 7, 6, 5, 4, 3, 2]) % 11) % 11;

        return $check !== 10 && $check === (int) $digits[9];
    }
}
