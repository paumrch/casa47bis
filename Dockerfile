# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# CASA47bis · imagen de producción
#
# Elección de runtime: FrankenPHP en vez de php-fpm + nginx.
#
#   - Un solo proceso sirviendo HTTP/1.1, HTTP/2 y HTTP/3, sin un nginx aparte
#     que mantener, versionar y sincronizar con el PHP-FPM que hay detrás.
#   - Menos superficie: una imagen, un proceso, un log, un healthcheck.
#   - Es un binario Go que embebe Caddy + el SAPI de PHP: sigue siendo PHP
#     estándar (mismo `php.ini`, mismas extensiones vía pecl/docker-php-ext),
#     así que no ata la aplicación a nada propietario de un proveedor de nube.
#   - "Worker mode" (bootea el framework una vez y sirve requests sobre el
#     mismo proceso) es opcional y NO se activa aquí: la tesis del proyecto
#     es portabilidad y simplicidad de abandono, no el último milisegundo de
#     rendimiento. Request por request, como cualquier php-fpm, es más fácil
#     de razonar y de migrar a otro runtime si algún día hiciera falta.
#
# Cuatro etapas: dependencias PHP (composer), assets (node/vite), imagen de
# ejecución. Cada etapa cachea capas independientes de las demás.
# ---------------------------------------------------------------------------

ARG PHP_VERSION=8.4

# ---------------------------------------------------------------------------
# Etapa 1: dependencias de Composer (sin dev, sin scripts: no hay artisan aún)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --no-progress \
    --no-autoloader \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# ---------------------------------------------------------------------------
# Etapa 2: assets front-end (Tailwind + Vite)
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* .npmrc ./
RUN [ -f package-lock.json ] && npm ci --ignore-scripts || npm install --ignore-scripts

COPY resources ./resources
COPY vite.config.js ./
COPY public ./public

RUN npm run build

# ---------------------------------------------------------------------------
# Etapa 3: imagen final — FrankenPHP (Caddy + PHP 8.4) sobre Alpine
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php${PHP_VERSION}-alpine AS app

# Extensiones necesarias:
#   pdo_pgsql  -> único almacén de datos (app, colas, sesiones, caché)
#   intl       -> formato de fechas/números en castellano
#   opcache    -> rendimiento en producción
#   zip        -> generación/lectura de expedientes comprimidos
#   soap       -> integración con SCSP (Administración)
RUN install-php-extensions \
    pdo_pgsql \
    intl \
    opcache \
    zip \
    soap

# php.ini de producción: errores a log (no a pantalla), límites razonables.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache.ini "$PHP_INI_DIR/conf.d/zz-opcache.ini"
COPY docker/php/production.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

# Copiamos el código de la aplicación primero (cambia poco entre commits que
# solo tocan dependencias) y las capas que más varían al final, para
# aprovechar la caché de build.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
    && chmod +x docker/entrypoint.sh \
    && chown -R www-data:www-data /app

USER www-data

ENV SERVER_PORT=8000
EXPOSE 8000

# /up es la ruta de salud por defecto de Laravel 13 (routes/console.php /
# bootstrap/app.php la registran junto al resto del framework).
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
    CMD curl -fsS "http://127.0.0.1:${SERVER_PORT}/up" || exit 1

ENTRYPOINT ["docker/entrypoint.sh"]

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
