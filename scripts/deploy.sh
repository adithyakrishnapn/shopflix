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
echo "Step 3: Initializing Database (Fresh Migrate & Seed)..."

# We use migrate:fresh to wipe any half-created tables from previous failed attempts.
# This ensures a clean slate as per the "Start from Scratch" flow.
echo "Running migrate:fresh..."
php artisan migrate:fresh --force --seed

# Only mark as installed if we're auto-seeding data
# If BAGISTO_SEED_BASE_DATA is true, data was seeded, so mark as installed
# Otherwise, user needs to visit /install to complete setup
if [ "$BAGISTO_SEED_BASE_DATA" = "true" ]; then
    echo "Auto-seed enabled - marking as installed"
    touch storage/installed
    chown www-data:www-data storage/installed
else
    echo "Auto-seed disabled - user must complete installation via /install"
    # Ensure no installed file exists so installer redirects work
    rm -f storage/installed
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




