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

# Create queue tables if using database queue
php artisan queue:table --force
php artisan migrate --force

# Ensure system admin exists
php artisan admin:ensure

# Ensure demo data is available
php artisan demo:ensure

# Create storage symlink
php artisan storage:link

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/storage

# Start supervisor
exec "$@"