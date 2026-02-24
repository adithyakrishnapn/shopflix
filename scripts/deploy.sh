#!/bin/sh

# Link storage
php artisan storage:link

# Check if migrations have run. If not, initialize project.
# We use a simple table check.
if ! php artisan migrate:status > /dev/null 2>&1; then
    echo "First time setup: Initializing project..."
    php artisan project:init --clean
else
    echo "Updating existing installation: Running migrations..."
    php artisan migrate --force
fi

# Cache configuration and routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"

