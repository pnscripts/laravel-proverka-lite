<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use PNScripts\ProverkaLite\Support\Lnch as LnchNumber;

/**
 * Validates an LNCh (personal number of a foreigner): ten digits and the check digit.
 */
class Lnch implements ValidationRule
{
    public static function make(): static
    {
        return new static;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! LnchNumber::isValid($value)) {
            $fail('proverka-lite::validation.bg_lnch')->translate();
        }
    }
}
