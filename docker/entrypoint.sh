#!/bin/sh
# Entrypoint de producción: cachea configuración/rutas/vistas con las
# variables de entorno reales del contenedor (no en build time, porque en
# build time no conocemos APP_KEY, DB_*, etc. de este despliegue concreto),
# enlaza el disco público y arranca FrankenPHP.
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force || true

exec "$@"
