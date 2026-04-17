FROM php:8.4-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    zip \
    postgresql-client \
    cron \
    && docker-php-ext-install pdo pdo_pgsql zip pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP for file uploads
COPY docker/laravel/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Copy startup script
COPY docker/laravel/start-laravel.sh /usr/local/bin/start-laravel.sh
RUN chmod +x /usr/local/bin/start-laravel.sh

CMD ["/usr/local/bin/start-laravel.sh"]

