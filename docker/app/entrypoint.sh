#!/bin/sh
set -e

cd /var/www/html

# Install composer dependencies if vendor is missing (útil cuando se monta ./:/var/www/html)
if [ ! -f "vendor/autoload.php" ]; then
    echo "[entrypoint] Installing composer dependencies..."
    composer install --prefer-dist --no-interaction --no-progress
fi

# Generate Passport keys if missing
if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then
    echo "[entrypoint] Generating Passport keys..."
    php artisan passport:keys --force
fi

# Generate Swagger docs
echo "[entrypoint] Generating Swagger docs..."
php artisan l5-swagger:generate

# Run pending migrations
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

exec "$@"
