#!/bin/sh

# Run migrations
php artisan migrate --force

# Cache configuration and routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
