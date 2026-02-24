#!/bin/sh

# Ensure .env exists (Render uses environment variables, but some Bagisto helpers check for the file)
if [ ! -f .env ]; then
    echo "Creating dummy .env file..."
    touch .env
fi

# Link storage
php artisan storage:link

# Check if migrations have run. If not, initialize project.
if ! php artisan migrate:status > /dev/null 2>&1; then
    echo "First time setup: Initializing project..."
    php artisan project:init --clean
    
    # Mark as installed for Bagisto's middleware
    touch storage/installed
else
    echo "Updating existing installation: Running migrations..."
    php artisan migrate --force
fi

# Ensure storage/installed exists if database is already ready
if [ ! -f storage/installed ]; then
    touch storage/installed
fi

# Cache configuration and routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"


