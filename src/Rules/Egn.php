<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Rules;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use PNScripts\ProverkaLite\Support\Egn as EgnNumber;

/**
 * Validates an EGN: ten digits, a real birth date with the century encoded in
 * the month, and the check digit. Optionally requires a sex or a minimum age,
 * and rejects birth dates in the future (on by default).
 */
class Egn implements ValidationRule
{
    protected ?string $sex = null;

    protected ?int $minAge = null;

    protected bool $allowFuture = false;

    public static function make(): static
    {
        return new static;
    }

    public function male(): static
    {
        return $this->sex(EgnNumber::MALE);
    }

    public function female(): static
    {
        return $this->sex(EgnNumber::FEMALE);
    }

    public function sex(string $sex): static
    {
        if (! in_array($sex, [EgnNumber::MALE, EgnNumber::FEMALE], true)) {
            throw new InvalidArgumentException('Sex must be "male" or "female".');
        }

        $this->sex = $sex;

        return $this;
    }

    public function minAge(int $years): static
    {
        $this->minAge = $years;

        return $this;
    }

    public function allowFutureDates(bool $allow = true): static
    {
        $this->allowFuture = $allow;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! EgnNumber::isValid($value)) {
            $fail('proverka-lite::validation.bg_egn')->translate();

            return;
        }

        $birth = EgnNumber::birthDate($value);

        if (! $this->allowFuture && $birth > new DateTimeImmutable('today')) {
            $fail('proverka-lite::validation.bg_egn')->translate();

            return;
        }

        if ($this->sex !== null && EgnNumber::sex($value) !== $this->sex) {
            $fail('proverka-lite::validation.bg_egn_sex')->translate([
                'sex' => __('proverka-lite::validation.sexes.'.$this->sex),
            ]);

            return;
        }

        if ($this->minAge !== null && (EgnNumber::age($value) ?? -1) < $this->minAge) {
            $fail('proverka-lite::validation.bg_egn_min_age')->translate(['years' => $this->minAge]);
        }
    }
}
