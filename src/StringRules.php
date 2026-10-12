<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

/**
 * Maps the string rule names (bg_egn, bg_eik:9, bg_vat:company, ...) to the rule objects,
 * so both notations validate and report errors the same way.
 */
final class StringRules
{
    public const NAMES = ['bg_egn', 'bg_lnch', 'bg_egn_or_lnch', 'bg_eik', 'bg_vat'];

    /**
     * @param  list<string>  $parameters
     */
    public static function make(string $name, array $parameters = []): ValidationRule
    {
        return match ($name) {
            'bg_egn' => self::egn($parameters),
            'bg_lnch' => Rules\Lnch::make(),
            'bg_egn_or_lnch' => Rules\PersonalNumber::make(),
            'bg_eik' => Rules\Eik::make(isset($parameters[0]) ? (int) $parameters[0] : null),
            'bg_vat' => self::vat($parameters),
            default => throw new InvalidArgumentException("Unknown rule [$name]."),
        };
    }

    /**
     * Runs the rule and returns the translated failure messages (empty when it passes).
     *
     * @param  list<string>  $parameters
     * @return list<string>
     */
    public static function failures(string $name, array $parameters, string $attribute, mixed $value): array
    {
        $pending = [];
        $translator = app('translator');

        self::make($name, $parameters)->validate($attribute, $value, function (string $message) use (&$pending, $translator) {
            return $pending[] = new PotentiallyTranslatedString($message, $translator);
        });

        return array_map(fn (PotentiallyTranslatedString $s) => $s->toString(), $pending);
    }

    public static function passes(string $name, array $parameters, string $attribute, mixed $value): bool
    {
        return self::failures($name, $parameters, $attribute, $value) === [];
    }

    /**
     * Replacer: when the application has no own "validation.<rule>" line, use the
     * package message produced by the rule object for this value.
     */
    public static function replace(string $message, string $attribute, string $rule, array $parameters, Validator $validator): string
    {
        if ($message !== 'validation.'.$rule && $message !== 'validation.custom.'.$attribute.'.'.$rule) {
            return $message;
        }

        $failures = self::failures($rule, $parameters, $attribute, Arr::get($validator->getData(), $attribute));
        $text = $failures[0] ?? (string) __('proverka-lite::validation.'.$rule);

        return str_replace(
            [':attribute', ':Attribute', ':ATTRIBUTE'],
            [$name = $validator->getDisplayableAttribute($attribute), ucfirst($name), strtoupper($name)],
            $text
        );
    }

    /**
     * bg_egn, bg_egn:male, bg_egn:female,18, bg_egn:18
     */
    private static function egn(array $parameters): Rules\Egn
    {
        $rule = Rules\Egn::make();

        foreach ($parameters as $parameter) {
            match (true) {
                ctype_digit((string) $parameter) => $rule->minAge((int) $parameter),
                default => $rule->sex((string) $parameter),
            };
        }

        return $rule;
    }

    /**
     * bg_vat, bg_vat:company, bg_vat:person, plus "no_prefix".
     */
    private static function vat(array $parameters): Rules\Vat
    {
        $rule = Rules\Vat::make();

        foreach ($parameters as $parameter) {
            match ($parameter) {
                'company' => $rule->companiesOnly(),
                'person' => $rule->personsOnly(),
                'no_prefix' => $rule->withoutPrefix(),
                default => throw new InvalidArgumentException("Unknown bg_vat option [$parameter]."),
            };
        }

        return $rule;
    }
}
