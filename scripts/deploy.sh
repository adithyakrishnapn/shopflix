#!/bin/sh

# Ensure .env exists (Render uses environment variables, but some Bagisto helpers check for the file)
if [ ! -f .env ]; then
    echo "Creating dummy .env file..."
    touch .env
fi

# Fix permissions
echo "Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Link storage
echo "Linking storage..."
php artisan storage:link

# Test database connection
echo "Testing database connection..."
if ! php artisan db:monitor > /dev/null 2>&1; then
    echo "ERROR: Could not connect to the database. Check Render Logs for DB variables."
fi

# Check if migrations table exists. If not, it's a fresh DB.
echo "Checking installation status..."
if ! php artisan migrate:status > /dev/null 2>&1; then
    echo "Fresh database detected. Initializing project from template..."
    
    # Run init. If it fails, EXIT so we don't mark as installed falsely.
    if php artisan project:init --clean; then
        echo "Initialization successful."
        touch storage/installed
        chown www-data:www-data storage/installed
    else
        echo "CRITICAL ERROR: Database initialization failed!"
        # Exit with 1 to tell Render the deploy failed
        exit 1
    fi
else
    echo "Existing database detected. Skipping initialization."
    # Ensure flag exists if migrations are already present
    if [ ! -f storage/installed ]; then
        touch storage/installed
        chown www-data:www-data storage/installed
    fi
    # Run migrations for any minor updates
    php artisan migrate --force
fi

# Cache configuration
echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM and Nginx
echo "Starting services..."
php-fpm -D
nginx -g "daemon off;"




