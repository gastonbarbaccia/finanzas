# Mis Finanzas: gastos, deudas y asesor IA

Aplicación web en **Laravel 13 + MySQL 8**, dockerizada, para registrar gastos, controlar deudas, ver métricas y gráficos, y pedir recomendaciones a una IA (Gemini, OpenAI o Claude).

## Puesta en marcha

Requisitos: Docker y Docker Compose.

```bash
cp .env.example .env          # editá las contraseñas de la base y, si querés, las API keys
docker compose up -d --build  # el primer build tarda unos minutos (descarga Laravel)
```

Abrí http://localhost:8080 y creá tu cuenta. Al registrarte se crean categorías por defecto.

La `APP_KEY` se genera sola en el primer arranque y las migraciones corren automáticamente en cada inicio.

### Datos de prueba (opcional)

```bash
docker compose exec app php artisan db:seed --class=DemoSeeder
```

Crea el usuario `demo@example.com` / `demo1234` con seis meses de gastos y dos deudas, ideal para ver los gráficos y probar la IA.

## Funcionalidades

- **Resumen:** gasto del mes y variación contra el anterior, proyección a fin de mes, promedio diario, deuda pendiente, pagos mínimos, porcentaje del ingreso comprometido y dinero libre estimado.
- **Gráficos (Chart.js):** evolución de 12 meses, distribución por categoría, medios de pago, presupuesto contra gastado y progreso de cada deuda.
- **Gastos:** alta, edición, baja, filtros por mes, categoría y texto.
- **Categorías y presupuestos:** color y presupuesto mensual opcional por categoría.
- **Deudas:** tarjetas, préstamos, hipotecas y deudas personales con tasa, pago mínimo, vencimiento e historial de pagos.
- **Asesor IA:** genera un diagnóstico, alertas, recomendaciones con montos, estrategia de pago de deudas (avalancha o bola de nieve) y un plan para los próximos 30 días. Los análisis quedan guardados.

## Configurar la IA

Cargá al menos una clave en `.env` y reiniciá con `docker compose restart app`:

| Proveedor | Variable | Dónde obtenerla |
|---|---|---|
| Google Gemini | `GEMINI_API_KEY` | https://aistudio.google.com/apikey (tiene capa gratuita) |
| OpenAI | `OPENAI_API_KEY` | https://platform.openai.com/api-keys |
| Anthropic Claude | `ANTHROPIC_API_KEY` | https://console.anthropic.com |

El modelo de cada proveedor se cambia con `GEMINI_MODEL`, `OPENAI_MODEL` y `ANTHROPIC_MODEL`. Si configurás varias claves, podés elegir el proveedor en cada análisis.

**Privacidad:** a la IA solo se envían totales agregados (gasto por categoría y mes, presupuestos, ingreso, saldos y tasas de las deudas). No se envían nombre, email ni las descripciones de los gastos. La lógica está en `app/Services/FinanceMetrics.php` (`aiSnapshot`) y `app/Services/AiAdvisor.php`.

## Estructura

El `Dockerfile` descarga el esqueleto oficial de Laravel 13 y le superpone la carpeta `src/`, que contiene solo el código propio:

```
src/
├── app/Http/Controllers/   Auth, Dashboard, Expense, Category, Debt, DebtPayment, Ai, Settings
├── app/Models/             User, Category, Expense, Debt, DebtPayment, AiRecommendation
├── app/Services/           FinanceMetrics (métricas) y AiAdvisor (Gemini, OpenAI, Claude)
├── config/                 ai.php y finance.php
├── database/               migraciones y DemoSeeder
├── lang/es/                traducciones de validación y paginación
├── resources/views/        vistas Blade (Tailwind + Chart.js)
└── routes/web.php
```

Para desarrollar con recarga en vivo podés montar `./src/app`, `./src/resources` y `./src/routes` como volúmenes en `docker-compose.yml`.

## Seguridad incluida

Contraseñas con bcrypt, límite de intentos en login, registro y llamadas a la IA, protección CSRF, validación de todos los datos en el servidor, control de que cada gasto, deuda o análisis pertenezca al usuario (devuelve 404 si no), sesiones cifradas, la respuesta de la IA se renderiza como Markdown descartando HTML, API keys solo en `.env` y nunca en logs, y cabeceras de seguridad en Apache.

## Para producción

- `APP_ENV=production`, `APP_DEBUG=false` y `SESSION_SECURE_COOKIE=true`.
- Poné un proxy con HTTPS adelante (Caddy, Traefik o Nginx con Let's Encrypt).
- No expongas el puerto de MySQL y usá contraseñas fuertes.
- Tailwind se carga desde CDN por simplicidad; para producción conviene compilarlo con Vite.
- Hacé backups del volumen `db_data` (por ejemplo con `mysqldump`).

## Publicar en un VPS (por ejemplo Hostinger)

1. En el VPS, instalá el sistema "Ubuntu 24.04 con Docker" y apuntá un registro A de tu dominio a la IP del VPS.
2. Subí el proyecto (con `git clone` o `scp -r finanzas root@IP_DEL_VPS:/opt/`) y entrá por SSH a la carpeta.
3. `cp .env.example .env` y ajustá: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tudominio.com`, `APP_DOMAIN=tudominio.com`, `TRUSTED_PROXIES=*`, `SESSION_SECURE_COOKIE=true`, contraseñas fuertes y API keys.
4. Levantá con el archivo de producción, que agrega Caddy con HTTPS automático:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
   ```
5. Firewall: dejá abiertos solo los puertos 22, 80 y 443.

## Problemas comunes

- **Ver logs:** `docker compose logs -f app` y `docker compose exec app tail -f storage/logs/laravel.log`.
- **Cambié el `.env` y no toma los valores:** `docker compose restart app`.
- **Reiniciar todo desde cero (borra los datos):** `docker compose down -v && docker compose up -d --build`.
