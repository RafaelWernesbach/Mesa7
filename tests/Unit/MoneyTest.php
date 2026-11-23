<?php

use App\Support\Money;
use InvalidArgumentException;

it('converts decimal strings to integer cents and back without floating point arithmetic', function () {
    expect(Money::toCents('0.05'))->toBe(5)
        ->and(Money::toCents('1234.5'))->toBe(123450)
        ->and(Money::fromCents(123450))->toBe('1234.50')
        ->and(Money::formatBrazilian('1234.50'))->toBe('R$ 1.234,50');
});

it('rejects malformed, negative and over-precision monetary values', function (string $value) {
    expect(fn () => Money::toCents($value))->toThrow(InvalidArgumentException::class);
})->with(['-1.00', '1,25', '1.234', '100000000.00', 'abc']);
