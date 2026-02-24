#!/bin/sh

# Exit on error
set -e

echo "--- Starting Standard Deployment Script ---"

# 1. Permission & Directory Setup
echo "Step 1: Setting up directories and permissions..."
mkdir -p storage/logs \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         storage/app/public \
         bootstrap/cache

# Ensure .env exists (Render handles vars, but Bagisto needs the file)
if [ ! -f .env ]; then
    echo "Creating dummy .env file..."
    touch .env
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 2. Storage Link
echo "Step 2: Linking storage..."
# Force LOG_CHANNEL=stderr for all artisan commands
export LOG_CHANNEL=stderr
php artisan storage:link || echo "Storage already linked."

# 3. Standard Laravel/Bagisto Initialization (as per README)
echo "Step 3: Initializing Database (Migrate & Seed)..."

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Run seeding
# Note: In production, you might only want to seed once. 
# But to match the README's "Start from Scratch" flow:
echo "Running database seeder..."
php artisan db:seed --force

# Mark as installed for Bagisto
if [ ! -f storage/installed ]; then
    touch storage/installed
    chown www-data:www-data storage/installed
fi

# 4. Optimization
echo "Step 4: Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Start Services
echo "Step 5: Starting services (PHP-FPM & Nginx)..."
php-fpm -D
nginx -g "daemon off;"




