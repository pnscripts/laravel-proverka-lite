<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PNScripts\ProverkaLite\Support\Egn;
use PNScripts\ProverkaLite\Support\Eik;
use PNScripts\ProverkaLite\Support\Lnch;
use PNScripts\ProverkaLite\Support\Vat;

/**
 * Synthetic numbers only; expected check digits were computed with a separate script.
 */
class NumbersTest extends TestCase
{
    public function test_lnch(): void
    {
        $this->assertTrue(Lnch::isValid('1002003008'));
        $this->assertTrue(Lnch::isValid('9876543217'));
        $this->assertFalse(Lnch::isValid('9876543210'));
        $this->assertFalse(Lnch::isValid('987654321'));
        $this->assertSame('1002003008', Lnch::generate('100200300'));
    }

    public function test_eik_nine_digits(): void
    {
        $this->assertTrue(Eik::isValid('201304175'));
        $this->assertTrue(Eik::isValid('314159268'));
        $this->assertFalse(Eik::isValid('201304176'));
        $this->assertFalse(Eik::isValid('20130417'));
    }

    public function test_eik_second_weight_set_is_used_when_the_first_gives_ten(): void
    {
        // 10000008: first pass 1*1 + 8*8 = 65, 65 mod 11 = 10; second pass 3 + 80 = 83 mod 11 = 6.
        $this->assertTrue(Eik::isValid('100000086'));
        $this->assertFalse(Eik::isValid('100000080'));
    }

    public function test_eik_thirteen_digits(): void
    {
        $this->assertTrue(Eik::isValid('2013041750014'));
        $this->assertTrue(Eik::isValid('2013041750014', 13));
        $this->assertFalse(Eik::isValid('2013041750014', 9));
        $this->assertFalse(Eik::isValid('2013041750015'));
        // Branch digits are fine but the 9-digit base is broken.
        $this->assertFalse(Eik::isValid('2013041760014'));
        $this->assertSame('2013041750014', Eik::generate('20130417001'));
    }

    public function test_eik_generator_round_trips(): void
    {
        for ($i = 0; $i < 300; $i++) {
            $base = str_pad((string) (10000000 + $i * 30011), 8, '0', STR_PAD_LEFT);
            $this->assertTrue(Eik::isValid(Eik::generate($base)));
            $this->assertTrue(Eik::isValid(Eik::generate($base.sprintf('%03d', $i % 1000)), 13));
        }
    }

    public function test_vat(): void
    {
        $egn = Egn::generate(new DateTimeImmutable('1984-03-08'), 'female', 12);

        $this->assertSame(Vat::KIND_EIK, Vat::kind('BG201304175'));
        $this->assertSame(Vat::KIND_EIK, Vat::kind('bg 201 304 175'));
        $this->assertSame(Vat::KIND_EGN, Vat::kind('BG'.$egn));
        $this->assertSame(Vat::KIND_LNCH, Vat::kind('BG1002003008'));
        $this->assertSame(Vat::KIND_OTHER, Vat::kind('BG1234567809'));
        $this->assertNull(Vat::kind('201304175'));
        $this->assertSame(Vat::KIND_EIK, Vat::kind('201304175', false));
        $this->assertNull(Vat::kind('BG201304176'));
        $this->assertNull(Vat::kind('BG2013041750014'));
        $this->assertNull(Vat::kind('DE201304175'));
        $this->assertTrue(Vat::isValid('BG201304175', [Vat::KIND_EIK]));
        $this->assertFalse(Vat::isValid('BG'.$egn, [Vat::KIND_EIK]));
        $this->assertSame('BG201304175', Vat::fromEik('201 304 175'));
        $this->assertNull(Vat::fromEik('2013041750014'));
    }
}
