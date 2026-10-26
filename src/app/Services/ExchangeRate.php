<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cotización del dólar de cada usuario.
 * Se obtiene de DolarApi.com (oficial, blue, MEP, tarjeta…) y se guarda en el usuario;
 * se actualiza como máximo una vez por hora. También se puede cargar a mano.
 */
class ExchangeRate
{
    public const TYPES = [
        'oficial'         => 'Oficial',
        'blue'            => 'Blue',
        'bolsa'           => 'MEP (bolsa)',
        'contadoconliqui' => 'Contado con liquidación',
        'tarjeta'         => 'Tarjeta',
        'cripto'          => 'Cripto',
        'manual'          => 'Manual (la cargo yo)',
    ];

    private const REFRESH_MINUTES = 60;

    /** Cotización vigente (pesos por dólar) o null si nunca se pudo obtener. */
    public static function for(User $user): ?float
    {
        if ($user->usd_rate_type !== 'manual') {
            $stale = ! $user->usd_rate_updated_at
                || $user->usd_rate_updated_at->lt(now()->subMinutes(self::REFRESH_MINUTES));

            if ($stale) {
                self::refresh($user);
            }
        }

        return $user->usd_rate ? (float) $user->usd_rate : null;
    }

    /** Consulta la API y guarda la cotización. Devuelve true si se actualizó. */
    public static function refresh(User $user): bool
    {
        if ($user->usd_rate_type === 'manual' || ! array_key_exists($user->usd_rate_type, self::TYPES)) {
            return false;
        }

        try {
            $url = rtrim(config('finance.usd_rates_url'), '/') . '/' . $user->usd_rate_type;
            $sell = Http::timeout(5)->acceptJson()->get($url)->throw()->json('venta');

            if (! is_numeric($sell) || $sell <= 0) {
                throw new \RuntimeException('Respuesta sin cotización de venta');
            }

            $user->forceFill(['usd_rate' => $sell, 'usd_rate_updated_at' => now()])->saveQuietly();

            return true;
        } catch (Throwable $e) {
            Log::warning('No se pudo actualizar la cotización del dólar', [
                'type'  => $user->usd_rate_type,
                'error' => mb_substr($e->getMessage(), 0, 200),
            ]);
            // Evita reintentar en cada página: se vuelve a probar en la próxima ventana
            if ($user->usd_rate) {
                $user->forceFill(['usd_rate_updated_at' => now()])->saveQuietly();
            }

            return false;
        }
    }

    /** Convierte un monto a pesos. */
    public static function toBase(float $amount, string $currency, ?float $rate): float
    {
        return $currency === 'USD' ? round($amount * (float) ($rate ?? 0), 2) : $amount;
    }
}
