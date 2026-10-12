<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use PNScripts\ProverkaLite\Support\Vat as VatNumber;

/**
 * Validates a Bulgarian VAT number: BG + EIK (9 digits) or BG + EGN / LNCh /
 * other 10-digit number. Offline check only; it does not query VIES.
 */
class Vat implements ValidationRule
{
    /** @var list<string>|null */
    protected ?array $kinds = null;

    protected bool $prefixRequired = true;

    public static function make(): static
    {
        return new static;
    }

    public function companiesOnly(): static
    {
        $this->kinds = [VatNumber::KIND_EIK];

        return $this;
    }

    public function personsOnly(): static
    {
        $this->kinds = [VatNumber::KIND_EGN, VatNumber::KIND_LNCH, VatNumber::KIND_OTHER];

        return $this;
    }

    public function withoutPrefix(): static
    {
        $this->prefixRequired = false;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! VatNumber::isValid($value, $this->kinds, $this->prefixRequired)) {
            $fail('proverka-lite::validation.bg_vat')->translate();
        }
    }
}
