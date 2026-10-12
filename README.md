# Proverka Lite by PN Scripts

Free Bulgarian identifier validation for Laravel 12 and 13 (MIT). Proverka
Lite checks the numbers Bulgarian forms ask for most, with their real
checksums and layouts, and gives English and Bulgarian error messages.

```bash
composer require pnscripts/laravel-proverka-lite
```

```php
$request->validate([
    'egn'  => 'required|bg_egn:18',        // EGN of an adult
    'id'   => 'bg_egn_or_lnch',            // the usual "ЕГН/ЛНЧ" field
    'eik'  => 'bg_eik',                    // 9 or 13 digits
    'vat'  => 'nullable|bg_vat:company',   // BG + EIK
]);

use PNScripts\ProverkaLite\Rules;
'egn' => [Rules\Egn::make()->female()->minAge(18)],
```

## Rules

| String rule | Rule object | Checks |
| --- | --- | --- |
| `bg_egn`, `bg_egn:male`, `bg_egn:female,18` | `Rules\Egn::make()->male()->minAge(18)` | ten digits, check digit, a real date with the century in the month; future dates rejected unless `allowFutureDates()` |
| `bg_lnch` | `Rules\Lnch::make()` | LNCh (personal number of a foreigner), weighted check digit |
| `bg_egn_or_lnch` | `Rules\PersonalNumber::make()` | either of the two |
| `bg_eik`, `bg_eik:9`, `bg_eik:13` | `Rules\Eik::make(9)` | EIK/BULSTAT, 9 digits (second weight set) and 13 digits (branch, both check digits) |
| `bg_vat`, `bg_vat:company`, `bg_vat:person`, `bg_vat:no_prefix` | `Rules\Vat::make()->companiesOnly()` | `BG` + EIK, or `BG` + EGN/LNCh/other 10-digit number |

Helpers: `Support\Egn::birthDate()`, `::sex()`, `::age()`, `Support\Vat::kind()`,
`::fromEik()`, and generators of synthetic, checksum-valid numbers for
factories (`Egn::generate()`, `Lnch::generate()`, `Eik::generate()`).

Messages: `php artisan vendor:publish --tag=proverka-lite-lang`. Config
(`string_rules`): `--tag=proverka-lite-config`. Your own `validation.bg_egn`
line overrides the package message.

Proverka Lite checks format and checksums offline; it does not ask any
registry whether a number is issued. The full edition, Proverka, adds IBAN,
phone numbers with E.164 storage and an Eloquent cast, and postal codes:
https://pnscripts.com/marketplace/proverka. Uninstall Lite before installing
the full edition (both register the same rule names).

## Tests

```bash
composer install && vendor/bin/phpunit    # 40 tests on Orchestra Testbench
```

## Licence

MIT. Copyright (c) 2026 ПН СКРИПТС ЕООД (PN Scripts).
