#!/bin/sh

cd /var/www/html

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
fi

# Generate app key if not exists
php artisan key:generate --no-interaction --force

# Run migrations
php artisan migrate --force

# Create storage symlink
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix storage permissions
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/storage

# Start supervisor
exec "$@"
