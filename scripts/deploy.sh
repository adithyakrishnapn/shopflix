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
echo "Step 3: Checking database setup..."

# IMPORTANT: Do NOT run migrations here. The /install web interface handles that.
# This allows the installer wizard to:
# 1. Configure environment variables
# 2. Create database tables via runMigration()
# 3. Seed base data via runSeeder()
# 4. Create admin account
# 5. Mark as installed

# The /install endpoint checks if storage/installed exists to determine if setup is complete
# Ensure the installed marker does not exist - user must visit /install to complete setup
echo "Clearing installation marker - user must complete setup via /install"
rm -f storage/installed

# If BAGISTO_SEED_BASE_DATA is explicitly true, auto-seed (for automated CI/CD)
# Otherwise (default), skip seeding - installer will handle it
if [ "$BAGISTO_SEED_BASE_DATA" = "true" ]; then
    echo "Auto-seed enabled - running migrations and seeding..."
    php artisan migrate --force
    php artisan db:seed --force
    echo "Marking as installed"
    touch storage/installed
    chown www-data:www-data storage/installed
else
    echo "Auto-seed disabled - user must complete installation via /install web interface"
    # Migrations will be run by the installer when user visits /install
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




