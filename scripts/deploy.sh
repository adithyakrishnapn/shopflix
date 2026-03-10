#!/bin/sh

# Exit on error for critical startup commands only.
set -e

echo "--- Starting Standard Deployment Script ---"
echo "Runtime PORT: ${PORT:-10000}"

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

chown -R www-data:www-data storage bootstrap/cache || echo "Skipping chown (insufficient permissions)."
chmod -R 775 storage bootstrap/cache || echo "Skipping chmod update."

# 2. Storage Link
echo "Step 2: Linking storage..."
# Force LOG_CHANNEL=stderr for all artisan commands
export LOG_CHANNEL=stderr
php artisan storage:link || echo "Storage already linked or link failed."

# 3. Database & Installer Setup
echo "Step 3: Running database migrations..."

# Always run migrations during deployment (they're idempotent and safe)
# This prevents the fragile HTTP installer migration calls from timing out on slow servers
# Use 'migrate' (incremental) instead of 'migrate:fresh' (drop/recreate) - much faster on Render
echo "Running incremental migrations (fast, idempotent)..."
php artisan migrate --force --no-interaction || true

echo "Step 3b: Installer Setup..."

# Keep existing installation state by default.
# To force installer flow on next boot, set FORCE_INSTALLER=true in environment.
if [ "$FORCE_INSTALLER" = "true" ]; then
    echo "FORCE_INSTALLER=true: clearing installation marker"
    rm -f storage/installed
fi

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
php artisan config:cache || echo "config:cache failed, continuing startup"
php artisan route:cache || echo "route:cache failed, continuing startup"
php artisan view:cache || echo "view:cache failed, continuing startup"

# 5. Start Services
echo "Step 5: Starting services (PHP-FPM & Nginx)..."
echo "Step 5a: Configuring nginx listen port..."
sed -i "s/listen 80;/listen ${PORT:-10000};/" /etc/nginx/sites-available/default

echo "Step 5b: Starting services (PHP-FPM & Nginx)..."
php-fpm -D
exec nginx -g "daemon off;"




