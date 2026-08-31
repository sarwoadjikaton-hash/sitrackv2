#!/bin/sh
set -e

# Laravel writable directories. Avoid chmod 777 across the whole storage tree.
mkdir -p /var/www/html/storage/framework/{cache,sessions,views} \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Bootstrap .env and application key on first boot.
if [ ! -f /var/www/html/.env ] && [ -f /var/www/html/.env.example ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

if [ -f /var/www/html/.env ] && ! grep -q '^APP_KEY=base64:' /var/www/html/.env; then
    php artisan key:generate --force --no-interaction || true
fi

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize:clear >/dev/null 2>&1 || true

exec "$@"
