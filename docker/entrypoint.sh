#!/bin/sh
set -e
cd /var/www/app

if [ ! -f .env ]; then
  echo "ERROR: no se encontró .env. Copiá .env.example a .env en la raíz del proyecto."
  exit 1
fi

# Genera APP_KEY la primera vez
if ! grep -q "^APP_KEY=base64:" .env; then
  php artisan key:generate --force
fi

# Espera a que la base de datos responda (hasta ~60 s) antes de migrar
DB_HOST_VALUE=$(grep -E "^DB_HOST=" .env | cut -d= -f2 | tr -d '"' | tr -d "'")
DB_HOST_VALUE=${DB_HOST_VALUE:-db}
i=1
until php artisan migrate --force; do
  if [ "$i" -ge 30 ]; then
    echo "ERROR: no se pudo conectar a la base de datos en '$DB_HOST_VALUE'."
    if ! getent hosts "$DB_HOST_VALUE" >/dev/null 2>&1; then
      echo "El nombre '$DB_HOST_VALUE' no se resuelve: el contenedor no está en la misma red que MySQL."
      echo "Probá: docker compose down && docker compose up -d"
    fi
    exit 1
  fi
  echo "Esperando la base de datos ($i/30)..."
  i=$((i + 1))
  sleep 2
done
php artisan config:clear >/dev/null
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
