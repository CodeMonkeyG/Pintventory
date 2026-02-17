FROM php:8.4-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP for file uploads
COPY docker/laravel/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

CMD ["php-fpm"]

