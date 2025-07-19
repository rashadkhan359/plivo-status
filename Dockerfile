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

# Copy Nginx config
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Make entrypoint executable
RUN chmod +x /var/www/html/docker/entrypoint.sh

# Create SQLite database directory
RUN mkdir -p /var/www/html/database && \
    chmod -R 775 /var/www/html/database

# Build assets
RUN npm run build

# Clean up node_modules to reduce image size
RUN rm -rf node_modules

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
