FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    libicu-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    default-mysql-client

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip calendar curl


# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files with correct ownership
COPY --chown=www-data:www-data . /var/www

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy Nginx config
COPY nginx.conf /etc/nginx/sites-available/default

# Override default PHP-FPM pool to increase worker count and fix 504 timeouts
COPY php-fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

# Expose port 80
EXPOSE 80

# Run deployment script and start Nginx/PHP-FPM
RUN chmod +x scripts/deploy.sh
CMD ["/var/www/scripts/deploy.sh"]
