FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    nodejs \
    npm \
    bash

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
WORKDIR /var/www
COPY . .

# Set production environment
ENV APP_ENV=production
ENV APP_DEBUG=false

# Install PHP dependencies (no dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install & build frontend assets
# npm install (et non npm ci) car le projet ne fournit pas de package-lock.json
RUN npm install --no-audit --no-fund && \
    npm run build && \
    rm -rf node_modules

# Create runtime directories
RUN mkdir -p \
    /var/log/supervisor \
    /run/php \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    public/storage \
    public/uploads \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
        public/storage \
        public/uploads \
    && chmod -R 775 storage bootstrap/cache

# Configure Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx-site.conf /etc/nginx/http.d/default.conf

# Configure PHP
COPY docker/php.ini $PHP_INI_DIR/conf.d/custom.ini
COPY docker/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# Configure Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80 443

VOLUME ["/var/www/storage", "/var/www/public/storage", "/var/www/bootstrap/cache"]

ENTRYPOINT ["/entrypoint.sh"]
