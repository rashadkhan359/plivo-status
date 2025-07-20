#!/bin/bash

set -e

cd /var/www/html

# Function to log with timestamp
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting application deployment..."

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/html/database/database.sqlite ]; then
    log "Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
else
    log "SQLite database already exists"
fi

# Wait for database to be ready (for SQLite, this is immediate)
log "Database is ready"

# Generate app key if not exists
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    log "Generating application key..."
    php artisan key:generate --no-interaction --force
fi

# Run composer post-autoload-dump with runtime environment
log "Running composer post-autoload-dump..."
composer run-script post-autoload-dump --no-interaction

# Run database migrations
log "Running database migrations..."
php artisan migrate --force

# Ensure demo data is available
log "Ensuring demo data is available..."
php artisan demo:ensure

# Create storage symlink
log "Creating storage symlink..."
php artisan storage:link

# Clear and cache configuration for production
log "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
log "Setting proper permissions..."
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# Ensure database permissions are correct
chown www-data:www-data /var/www/html/database/database.sqlite
chmod 664 /var/www/html/database/database.sqlite

log "Application deployment completed successfully!"

# Execute the main command
exec "$@"
