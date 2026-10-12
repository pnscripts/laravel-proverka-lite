<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Rules;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use PNScripts\ProverkaLite\Support\Egn as EgnNumber;
use PNScripts\ProverkaLite\Support\Lnch as LnchNumber;

/**
 * The common "ЕГН/ЛНЧ" form field: accepts a valid EGN (no future birth date) or a valid LNCh.
 */
class PersonalNumber implements ValidationRule
{
    public static function make(): static
    {
        return new static;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $egn = EgnNumber::isValid($value) && EgnNumber::birthDate($value) <= new DateTimeImmutable('today');

        if (! $egn && ! LnchNumber::isValid($value)) {
            $fail('proverka-lite::validation.bg_egn_or_lnch')->translate();
        }
    }
}
