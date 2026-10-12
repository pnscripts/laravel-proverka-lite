<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use PNScripts\ProverkaLite\Support\Eik as EikNumber;

/**
 * Validates an EIK / BULSTAT number with 9 or 13 digits and its check digits.
 */
class Eik implements ValidationRule
{
    public function __construct(protected ?int $length = null)
    {
        if ($length !== null && ! in_array($length, [9, 13], true)) {
            throw new InvalidArgumentException('An EIK has 9 or 13 digits.');
        }
    }

    public static function make(?int $length = null): static
    {
        return new static($length);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! EikNumber::isValid($value, $this->length)) {
            $fail($this->length ? 'proverka-lite::validation.bg_eik_length' : 'proverka-lite::validation.bg_eik')
                ->translate(['digits' => $this->length]);
        }
    }
}
