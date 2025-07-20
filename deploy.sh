#!/bin/bash

set -e

echo "🚀 Plivo Status Deployment Script"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "artisan file not found. Make sure you're in the Laravel project root."
    exit 1
fi

# Environment setup
if [ ! -f ".env" ]; then
    print_warning ".env file not found. Copying from .env.example..."
    cp .env.example .env
fi

# Install dependencies
print_status "Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

print_status "Installing Node.js dependencies..."
npm ci

# Generate app key if needed
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=base64:$" .env; then
    print_status "Generating application key..."
    php artisan key:generate
fi

# Create database if it doesn't exist
if [ ! -f "database/database.sqlite" ]; then
    print_status "Creating SQLite database..."
    touch database/database.sqlite
fi

# Run migrations
print_status "Running database migrations..."
php artisan migrate --force

# Ensure demo data exists
print_status "Ensuring demo data is available..."
php artisan demo:ensure

# Build assets
print_status "Building frontend assets..."
npm run build

# Cache configuration
print_status "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
print_status "Creating storage symlink..."
php artisan storage:link

# Set permissions
print_status "Setting proper permissions..."
chmod -R 775 storage bootstrap/cache
chmod 664 database/database.sqlite

print_status "Deployment completed successfully!"
echo ""
echo "🎉 Your application is ready!"
echo ""
echo "Demo page available at: /status/demo-org"
echo "To start the development server, run: php artisan serve" 