FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libsqlite3-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    nodejs \
    npm \
    sqlite3

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install composer dependencies
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy package files
COPY package.json package-lock.json ./

# Install Node.js dependencies (including dev dependencies for building)
RUN npm ci

# Copy application files
COPY . .

# Create directories for persistent data
RUN mkdir -p /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /var/www/html/database

# Create SQLite database file if it doesn't exist
RUN touch /var/www/html/database/database.sqlite

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/storage && \
    chmod -R 775 /var/www/html/bootstrap/cache && \
    chmod 664 /var/www/html/database/database.sqlite

# Copy Nginx config
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Build frontend assets
RUN npm run build

# Run composer scripts after copying all files
RUN composer run-script post-autoload-dump

# Expose port
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Default command
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
