<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function toCents(string|int $amount): int
    {
        $value = (string) $amount;

        if (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('O valor deve ser um decimal positivo com até duas casas.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function fromCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function formatBrazilian(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '00');

        return 'R$ '.number_format((int) $whole, 0, ',', '.').','.str_pad($fraction, 2, '0');
    }
}
