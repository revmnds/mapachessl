#!/bin/sh
set -e

# Ensure storage directories exist with correct permissions
mkdir -p /var/www/html/storage/app/acme \
         /var/www/html/storage/app/certificates \
         /var/www/html/storage/app/public \
         /var/www/html/storage/app/private \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# Sync fresh public assets from image to volume (updates on each deploy)
if [ -d /var/www/html/public-build ]; then
    cp -r /var/www/html/public-build/* /var/www/html/public/
fi

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Migrations run only in the container that sets RUN_MIGRATIONS (app), not in every worker.
# Postgres may still be starting after a host reboot, so retry for a while.
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running migrations..."
    for i in $(seq 1 15); do
        php /var/www/html/artisan migrate --force --no-interaction && break
        echo "Database not ready, retrying in 2s ($i/15)..."
        sleep 2
    done
fi

# Clear and cache config
php /var/www/html/artisan config:clear
php /var/www/html/artisan route:clear
php /var/www/html/artisan view:clear

# Execute the main command (php-fpm)
exec "$@"
