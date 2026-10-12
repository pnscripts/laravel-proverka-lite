<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Tests\Feature;

use DateTimeImmutable;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use PNScripts\ProverkaLite\Rules;
use PNScripts\ProverkaLite\StringRules;
use PNScripts\ProverkaLite\Support\Egn;
use PNScripts\ProverkaLite\Support\Eik;
use PNScripts\ProverkaLite\Support\Lnch;
use PNScripts\ProverkaLite\Tests\TestCase;

class ExtrasTest extends TestCase
{
    public function test_egn_or_lnch_accepts_either_and_nothing_else(): void
    {
        $egn = Egn::generate(new DateTimeImmutable('1979-11-30'), 'male', 40);
        $future = Egn::generate(new DateTimeImmutable('+1 year'), 'male');

        foreach ([$egn, '1002003008'] as $ok) {
            $this->assertTrue(Validator::make(['id' => $ok], ['id' => 'bg_egn_or_lnch'])->passes(), $ok);
            $this->assertTrue(Validator::make(['id' => $ok], ['id' => [Rules\PersonalNumber::make()]])->passes(), $ok);
        }

        $errors = Validator::make(['id' => '1234567890'], ['id' => 'bg_egn_or_lnch'], [], ['id' => 'EGN/LNCh'])->errors();
        $this->assertSame('The EGN/LNCh must be a valid EGN or LNCh.', $errors->first('id'));
        $this->assertTrue(Validator::make(['id' => $future], ['id' => 'bg_egn_or_lnch'])->fails(), 'future birth date');
    }

    public function test_string_rule_options_are_checked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StringRules::make('bg_vat', ['banks']);
    }

    public function test_eik_length_must_be_9_or_13(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rules\Eik::make(10);
    }

    public function test_generators_reject_bad_input(): void
    {
        foreach ([
            fn () => Lnch::generate('12345'),
            fn () => Eik::generate('1234567'),
            fn () => Egn::generate(new DateTimeImmutable('1990-01-01'), 'other'),
        ] as $i => $call) {
            try {
                $call();
                $this->fail("case $i did not throw");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_every_string_rule_has_messages_in_both_languages(): void
    {
        foreach (StringRules::NAMES as $name) {
            foreach (['en', 'bg'] as $locale) {
                $line = __('proverka-lite::validation.'.$name, [], $locale);
                $this->assertStringContainsString(':attribute', $line, "$locale $name");
            }
        }
    }

    public function test_many_generated_egns_round_trip(): void
    {
        $date = new DateTimeImmutable('1900-01-01');
        for ($i = 0; $i < 400; $i++) {
            $date = $date->modify('+73 days');
            $egn = Egn::generate($date, $i % 2 ? 'female' : 'male', $i % 500);
            $this->assertSame($date->format('Y-m-d'), Egn::birthDate($egn)?->format('Y-m-d'), $egn);
        }
    }
}
