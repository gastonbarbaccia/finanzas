<?php

namespace App\Providers;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Zona horaria desde APP_TIMEZONE, sin depender del config/app.php del esqueleto
        $timezone = config('finance.timezone', 'UTC');
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }

    public function boot(): void
    {
        Carbon::setLocale('es');

        // Uso en Blade: @money($monto)
        Blade::directive('money', fn ($expression) => "<?php echo e(\\App\\Support\\Money::format($expression)); ?>");

        // Detrás de Caddy: tomar la IP real del cliente (para el límite de intentos)
        // y detectar HTTPS correctamente.
        if ($proxies = config('finance.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
