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

# 3. Database & Installer Setup
echo "Step 3: Running database migrations..."

# Always run migrations during deployment (they're idempotent and safe)
# This prevents the fragile HTTP installer migration calls from timing out on slow servers
# Use 'migrate' (incremental) instead of 'migrate:fresh' (drop/recreate) - much faster on Render
echo "Running incremental migrations (fast, idempotent)..."
php artisan migrate --force --no-interaction || true

echo "Step 3b: Installer Setup..."

# The /install endpoint checks if storage/installed exists to determine if setup is complete
# Migrations are done, but seeding/admin setup still needs to come from installer
echo "Clearing installation marker - user must complete seeding & admin setup via /install"
rm -f storage/installed

# If BAGISTO_SEED_BASE_DATA is explicitly true, also auto-seed (for automated CI/CD)
# Otherwise (default), seeding will be handled by installer web interface
if [ "$BAGISTO_SEED_BASE_DATA" = "true" ]; then
    echo "Auto-seed enabled - running seeders..."
    php artisan db:seed --force
    echo "Marking as fully installed"
    touch storage/installed
    chown www-data:www-data storage/installed
else
    echo "Auto-seed disabled - user must complete seeding via /install web interface"
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




