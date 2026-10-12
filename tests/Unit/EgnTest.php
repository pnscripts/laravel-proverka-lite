<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Tests\Unit;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PNScripts\ProverkaLite\Support\Egn;

/**
 * All numbers here are synthetic: built from the checksum formula (independently
 * computed with a separate script) or by Egn::generate(). None belongs to a known person.
 */
class EgnTest extends TestCase
{
    public static function validNumbers(): array
    {
        return [
            '1990-05-15 male' => ['9005152466', '1990-05-15', 'male'],
            '2005-12-31 female' => ['0552310015', '2005-12-31', 'female'],
            '1850-01-02 male' => ['5021020005', '1850-01-02', 'male'],
            '1999-12-31 male' => ['9912319987', '1999-12-31', 'male'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_valid_numbers_decode_date_and_sex(string $egn, string $date, string $sex): void
    {
        $this->assertTrue(Egn::isValid($egn));
        $this->assertSame($date, Egn::birthDate($egn)->format('Y-m-d'));
        $this->assertSame($sex, Egn::sex($egn));
    }

    public static function invalidNumbers(): array
    {
        return [
            'wrong check digit' => ['9005152467'],
            'checksum fine, 29 Feb 1975 does not exist' => ['7502291349'],
            'checksum fine, 29 Feb 1900 does not exist' => ['0002290017'],
            'month 13' => ['9013150000'],
            'nine digits' => ['900515246'],
            'eleven digits' => ['90051524660'],
            'letters' => ['90051524A6'],
            'empty' => [''],
            'null' => [null],
            'array' => [['9005152466']],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numbers(mixed $egn): void
    {
        $this->assertFalse(Egn::isValid($egn));
        $this->assertNull(Egn::birthDate($egn));
        $this->assertNull(Egn::sex($egn));
    }

    public function test_spaces_are_ignored(): void
    {
        $this->assertTrue(Egn::isValid(' 900515 2466 '));
    }

    public function test_generated_numbers_cover_every_century_and_leap_days(): void
    {
        foreach (['1800-03-01', '1899-12-31', '1900-01-01', '1996-02-29', '2000-02-29', '2024-02-29', '2099-12-31'] as $date) {
            foreach (['male', 'female'] as $sex) {
                foreach ([0, 1, 250, 499] as $serial) {
                    $egn = Egn::generate(new DateTimeImmutable($date), $sex, $serial);

                    $this->assertTrue(Egn::isValid($egn), "$egn ($date, $sex, $serial)");
                    $this->assertSame($date, Egn::birthDate($egn)->format('Y-m-d'));
                    $this->assertSame($sex, Egn::sex($egn));
                }
            }
        }
    }

    public function test_every_wrong_check_digit_is_caught_on_a_generated_number(): void
    {
        $egn = Egn::generate(new DateTimeImmutable('1987-07-14'), 'female', 77);

        // Changing the check digit alone must always be caught.
        for ($d = 0; $d <= 9; $d++) {
            if ((string) $d !== $egn[9]) {
                $this->assertFalse(Egn::isValid(substr($egn, 0, 9).$d));
            }
        }
    }

    public function test_age(): void
    {
        $egn = Egn::generate(new DateTimeImmutable('2008-10-10'), 'male');

        $this->assertSame(18, Egn::age($egn, new DateTimeImmutable('2026-10-10')));
        $this->assertSame(17, Egn::age($egn, new DateTimeImmutable('2026-10-09')));
        $this->assertNull(Egn::age($egn, new DateTimeImmutable('2000-01-01')));
        $this->assertNull(Egn::age('1234567890'));
    }

    public function test_generate_rejects_bad_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Egn::generate(new DateTimeImmutable('2100-01-01'));
    }

    public function test_generate_rejects_bad_serial(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Egn::generate(new DateTimeImmutable('2000-01-01'), 'male', 500);
    }
}
