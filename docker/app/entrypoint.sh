#!/bin/sh
set -e

cd /var/www/html

if [ ! -f "vendor/autoload.php" ]; then
    echo "[entrypoint] Installing composer dependencies..."
    composer install --prefer-dist --no-interaction --no-progress
fi

if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then
    echo "[entrypoint] Generating Passport keys..."
    php artisan passport:keys --force
fi

echo "[entrypoint] Generating Swagger docs..."
php artisan l5-swagger:generate

exec "$@"
