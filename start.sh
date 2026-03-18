#!/bin/bash
set -e

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Warming up cache..."
php bin/console cache:warmup --env=prod

# Configure Apache to listen on Railway's PORT (defaults to 8080)
export PORT="${PORT:-8080}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
