<?php

return [
    'currency_symbol' => env('APP_CURRENCY_SYMBOL', '$'),
    'timezone'        => env('APP_TIMEZONE', 'UTC'),
    'currencies'      => ['ARS' => 'Pesos (ARS)', 'USD' => 'Dólares (USD)'],
    'usd_symbol'      => env('APP_USD_SYMBOL', 'US$'),
    'usd_rates_url'   => env('USD_RATES_URL', 'https://dolarapi.com/v1/dolares'),
    // Solo cuando la app está detrás de un proxy (Caddy en producción). Ej.: "*"
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    'default_categories' => [
        ['name' => 'Alimentación',    'color' => '#2F855A'],
        ['name' => 'Vivienda',        'color' => '#2B6CB0'],
        ['name' => 'Servicios',       'color' => '#6B46C1'],
        ['name' => 'Transporte',      'color' => '#C05621'],
        ['name' => 'Salud',           'color' => '#C53030'],
        ['name' => 'Entretenimiento', 'color' => '#D69E2E'],
        ['name' => 'Educación',       'color' => '#319795'],
        ['name' => 'Ropa',            'color' => '#B83280'],
        ['name' => 'Otros',           'color' => '#718096'],
    ],
];
