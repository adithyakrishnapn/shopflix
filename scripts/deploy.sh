#!/bin/sh

# Ensure .env exists (Render uses environment variables, but some Bagisto helpers check for the file)
if [ ! -f .env ]; then
    echo "Creating dummy .env file..."
    touch .env
fi

# Ensure storage directories exist and are writable
echo "Fixing permissions..."
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Link storage
echo "Linking storage..."
php artisan storage:link

# MySQL bypass flags for Aiven (Self-signed certs)
# We try to disable SSL entirely for the import to avoid the certificate chain error.
MYSQL_FLAGS="-h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE --ssl-mode=DISABLED"

# Test database connection
echo "Testing database connection..."
mysql --version
if ! mysql $MYSQL_FLAGS -e "SELECT 1" > /dev/null 2>&1; then
    echo "ERROR: Could not connect to the database via native client. Checking SSL bypass..."
    # Fallback to older SSL disable flag if needed
    MYSQL_FLAGS="-h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE --ssl=0"
fi

# Check if migrations table exists. If not, it's a fresh DB.
echo "Checking installation status..."
if ! mysql $MYSQL_FLAGS -e "DESCRIBE channels" > /dev/null 2>&1; then
    echo "Table 'channels' missing. Initializing database from native SQL template..."
    
    # Use mysql client for robust import with SSL disabled and PK requirement bypassed.
    # We prepend the SET statement to handle Aiven's primary key requirement for the session.
    if (echo "SET SESSION sql_require_primary_key = 0;"; cat database/master_template.sql) | mysql $MYSQL_FLAGS; then


        echo "Initialization successful."
        
        # Now run project:init ONLY for the cleanup Part (don't re-run import)
        # We'll skip the import in project:init if we can, or just trust the mysql import.
        # Bagisto needs the flag file.
        touch storage/installed
        chown www-data:www-data storage/installed
        
        # Run standard migrations to ensure everything is synced
        php artisan migrate --force
    else
        echo "CRITICAL ERROR: Native database initialization failed!"
        exit 1
    fi
else
    echo "Database already initialized. Skipping SQL import."
    # Ensure flag exists
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




