<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Tests\Feature;

use DateTimeImmutable;
use Illuminate\Support\Facades\Validator;
use PNScripts\ProverkaLite\Rules;
use PNScripts\ProverkaLite\Support\Egn;
use PNScripts\ProverkaLite\Tests\TestCase;

class ValidationTest extends TestCase
{
    private function errors(array $data, array $rules, array $attributes = []): array
    {
        return Validator::make($data, $rules, [], $attributes)->errors()->toArray();
    }

    public function test_rule_objects_pass_valid_synthetic_data(): void
    {
        $data = [
            'egn' => Egn::generate(new DateTimeImmutable('1990-05-15'), 'male', 123),
            'lnch' => '1002003008',
            'eik' => '201304175',
            'branch' => '2013041750014',
            'vat' => 'BG201304175',
        ];

        $this->assertSame([], $this->errors($data, [
            'egn' => ['required', Rules\Egn::make()->male()->minAge(18)],
            'lnch' => [Rules\Lnch::make()],
            'eik' => [Rules\Eik::make(9)],
            'branch' => [Rules\Eik::make(13)],
            'vat' => [Rules\Vat::make()->companiesOnly()],
        ]));
    }

    public function test_string_rules_pass_the_same_data(): void
    {
        $data = [
            'egn' => Egn::generate(new DateTimeImmutable('1984-03-08'), 'female', 12),
            'lnch' => '1002003008',
            'eik' => '2013041750014',
            'vat' => 'BG1002003008',
        ];

        $this->assertSame([], $this->errors($data, [
            'egn' => 'required|bg_egn:female,18',
            'lnch' => 'bg_lnch',
            'eik' => 'bg_eik:13',
            'vat' => 'bg_vat:person',
        ]));
    }

    public function test_english_messages(): void
    {
        $errors = $this->errors(
            ['egn' => '9005152467', 'eik' => '201304176', 'vat' => 'BG201304176'],
            ['egn' => Rules\Egn::make(), 'eik' => 'bg_eik:9', 'vat' => 'bg_vat'],
            ['vat' => 'VAT number'],
        );

        $this->assertSame(['The egn must be a valid EGN.'], $errors['egn']);
        $this->assertSame(['The eik must be a valid EIK/BULSTAT number with 9 digits.'], $errors['eik']);
        $this->assertSame(['The VAT number must be a valid Bulgarian VAT number.'], $errors['vat']);
    }

    public function test_bulgarian_messages(): void
    {
        $this->app->setLocale('bg');
        $young = Egn::generate(new DateTimeImmutable('-10 years'), 'male');

        $errors = $this->errors(
            ['egn' => $young, 'egn2' => $young, 'vat' => 'BG201304176'],
            ['egn' => 'bg_egn:18', 'egn2' => Rules\Egn::make()->female(), 'vat' => Rules\Vat::make()],
            ['egn' => 'ЕГН', 'egn2' => 'ЕГН на съпругата', 'vat' => 'ДДС номер'],
        );

        $this->assertSame(['Полето ЕГН трябва да съдържа ЕГН на лице на поне 18 години.'], $errors['egn']);
        $this->assertSame(['Полето ЕГН на съпругата трябва да съдържа ЕГН на жена.'], $errors['egn2']);
        $this->assertSame(['Полето ДДС номер трябва да съдържа валиден български ДДС номер.'], $errors['vat']);
    }

    public function test_future_birth_dates_are_rejected_unless_allowed(): void
    {
        $future = Egn::generate(new DateTimeImmutable('+2 years'), 'female');

        $this->assertArrayHasKey('egn', $this->errors(['egn' => $future], ['egn' => 'bg_egn']));
        $this->assertSame([], $this->errors(['egn' => $future], ['egn' => [Rules\Egn::make()->allowFutureDates()]]));
    }

    public function test_application_message_overrides_the_package_message(): void
    {
        $errors = Validator::make(
            ['egn' => '1'],
            ['egn' => 'bg_egn'],
            ['egn.bg_egn' => 'Custom :attribute text.'],
        )->errors()->toArray();

        $this->assertSame(['Custom egn text.'], $errors['egn']);
    }

    public function test_package_translations_can_be_published_and_are_loaded(): void
    {
        $this->assertSame('The :attribute must be a valid EGN.', __('proverka-lite::validation.bg_egn'));
        $this->assertSame('Полето :attribute трябва да съдържа валидно ЕГН.', __('proverka-lite::validation.bg_egn', [], 'bg'));
    }

    public function test_nullable_and_empty_values_follow_laravel_semantics(): void
    {
        $this->assertSame([], $this->errors(['egn' => null], ['egn' => 'nullable|bg_egn']));
        $this->assertSame([], $this->errors([], ['egn' => 'bg_egn']));
        $this->assertArrayHasKey('egn', $this->errors(['egn' => 'abc'], ['egn' => 'nullable|bg_egn']));
    }
}
