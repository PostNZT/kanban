#!/bin/bash
set -e

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Setting up messenger transport table..."
php bin/console messenger:setup-transports --no-interaction 2>/dev/null || true

echo "Installing assets..."
php bin/console assets:install public --env=prod

echo "Warming up cache..."
php bin/console cache:warmup --env=prod

# Ensure only one MPM is loaded
find /etc/apache2/mods-enabled -name 'mpm_*' -delete
a2enmod mpm_prefork 2>/dev/null || true

# Configure Apache to listen on Railway's PORT
PORT="${PORT:-8080}"
echo "Listen ${PORT}" > /etc/apache2/ports.conf
sed -i "s/__PORT__/${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Start messenger worker in background (auto-restarts on exit)
echo "Starting messenger worker..."
(while true; do
    php bin/console messenger:consume async \
        --time-limit=3600 \
        --memory-limit=128M \
        --env=prod \
        2>&1 | sed 's/^/[messenger] /'
    echo "[messenger] Worker exited, restarting in 2s..."
    sleep 2
done) &

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
