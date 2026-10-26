<?php

namespace App\Support;

class Money
{
    /** Formatea un monto. $currency: ARS (por defecto) o USD. */
    public static function format(float|int|string|null $value, int $decimals = 2, string $currency = 'ARS'): string
    {
        $value = (float) ($value ?? 0);
        $sign = $value < 0 ? '-' : '';
        $symbol = $currency === 'USD'
            ? config('finance.usd_symbol', 'US$')
            : config('finance.currency_symbol', '$');

        return $sign . $symbol . ' ' . number_format(abs($value), $decimals, ',', '.');
    }

    public static function symbol(string $currency = 'ARS'): string
    {
        return $currency === 'USD' ? config('finance.usd_symbol', 'US$') : config('finance.currency_symbol', '$');
    }
}
