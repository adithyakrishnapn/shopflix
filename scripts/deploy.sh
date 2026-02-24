#!/bin/sh

# Exit on error
set -e

echo "--- Starting Deployment Script ---"

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
# We force LOG_CHANNEL=stderr for all artisan commands to avoid permission issues in CLI
export LOG_CHANNEL=stderr
php artisan storage:link || echo "Storage already linked."

# 3. Database Initialization (Aiven Compatible)
echo "Step 3: Checking database status..."

# Build MySQL flags
MYSQL_FLAGS="-h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE --ssl-mode=DISABLED"

# Check if 'channels' table exists
if ! mysql $MYSQL_FLAGS -e "DESCRIBE channels" > /dev/null 2>&1; then
    echo "Database table 'channels' not found. Initializing from template..."
    
    # We attempt to disable the primary key requirement for the session (Aiven specific)
    # We also disable SSL for the import to avoid the self-signed cert error
    if (echo "SET SESSION sql_require_primary_key = 0;"; cat database/master_template.sql) | mysql $MYSQL_FLAGS; then
        echo "Database imported successfully."
        
        # Mark as installed for Bagisto
        touch storage/installed
        chown www-data:www-data storage/installed
        
        echo "Running migrations to sync schema..."
        php artisan migrate --force
    else
        echo "CRITICAL ERROR: Database import failed!"
        exit 1
    fi
else
    echo "Database tables already exist. Skipping import."
    if [ ! -f storage/installed ]; then
        touch storage/installed
        chown www-data:www-data storage/installed
    fi
    php artisan migrate --force
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




