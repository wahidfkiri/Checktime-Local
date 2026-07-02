#!/bin/bash
set -e

# Create .env from example if not exists
if [ ! -f /var/www/.env ]; then
    cp /var/www/.env.example /var/www/.env
    echo "Created .env from .env.example"
fi

# Update .env with docker-compose environment variables
sed -i "s|^APP_ENV=.*|APP_ENV=${APP_ENV:-production}|" /var/www/.env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=${APP_DEBUG:-false}|" /var/www/.env
sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST:-mysql}|" /var/www/.env
sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT:-3306}|" /var/www/.env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-checktime}|" /var/www/.env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-checktime_user}|" /var/www/.env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" /var/www/.env

# Generate APP_KEY if not set
CURRENT_KEY=$(grep '^APP_KEY=' /var/www/.env | cut -d= -f2)
if [ -z "$CURRENT_KEY" ] || [ "$CURRENT_KEY" = "" ]; then
    php /var/www/artisan key:generate --force
    echo "APP_KEY generated."
fi

# Ensure storage directories exist
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache
mkdir -p /var/www/public/storage
mkdir -p /var/www/public/uploads

# Set permissions for www-data
chown -R www-data:www-data \
    /var/www/storage \
    /var/www/bootstrap/cache \
    /var/www/public/storage \
    /var/www/public/uploads

chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Wait for MySQL
echo "Waiting for MySQL..."
for i in $(seq 1 30); do
    if php -r "new PDO('mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306}', '${DB_USERNAME:-checktime_user}', '${DB_PASSWORD}');" 2>/dev/null; then
        echo "MySQL ready."
        break
    fi
    echo "Attempt $i: MySQL not ready, waiting..."
    sleep 2
done

# Cache Laravel config (skip if APP_KEY missing)
php /var/www/artisan config:cache || true
php /var/www/artisan route:cache || true
php /var/www/artisan view:cache || true

# Run migrations
php /var/www/artisan migrate --force || true

# Storage link
php /var/www/artisan storage:link --force || true

# Start Supervisor (manages PHP-FPM + Nginx)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
