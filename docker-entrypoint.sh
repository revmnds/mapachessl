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

# Create SQLite database if it doesn't exist
DB_PATH="/var/www/html/database/database.sqlite"
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database..."
    touch "$DB_PATH"
fi
chown www-data:www-data "$DB_PATH"
chmod 664 "$DB_PATH"

# Also ensure the database directory has correct permissions
chown www-data:www-data /var/www/html/database
chmod 775 /var/www/html/database

# Run migrations if APP_KEY is set
if [ -n "$APP_KEY" ]; then
    echo "Running migrations..."
    php /var/www/html/artisan migrate --force --no-interaction || true
fi

# Enable WAL mode for SQLite (better concurrent read/write performance)
DB_PATH="/var/www/html/database/database.sqlite"
if [ -f "$DB_PATH" ]; then
    sqlite3 "$DB_PATH" "PRAGMA journal_mode=WAL;" 2>/dev/null || true
fi

# Clear and cache config
php /var/www/html/artisan config:clear
php /var/www/html/artisan route:clear
php /var/www/html/artisan view:clear

# Execute the main command (php-fpm)
exec "$@"
