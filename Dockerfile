# ---------------------------------------------------------------
# Finanzas Personales - Laravel 13 + Apache + PHP 8.3
# El esqueleto de Laravel se descarga en el build y luego se
# superpone el código propio de la app (carpeta src/).
# ---------------------------------------------------------------
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl bcmath \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 1) Esqueleto oficial de Laravel 13 (sin dependencias de desarrollo)
RUN composer create-project laravel/laravel:^13.0 app \
        --prefer-dist --no-interaction --no-scripts --no-dev \
    && rm -f app/routes/web.php

WORKDIR /var/www/app

# 2) Código de la aplicación (sobrescribe/agrega archivos al esqueleto)
COPY src/ /var/www/app/

RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/security.conf /etc/apache2/conf-available/security-headers.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN a2enconf security-headers \
    && sed -i "s/\r$//" /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
