#!/bin/sh

# Add the cron job for the Laravel scheduler
echo "* * * * * cd /var/www/html && /usr/local/bin/php artisan schedule:run >> /var/www/html/storage/logs/cron.log 2>&1" > /etc/cron.d/laravel-scheduler
chmod 0644 /etc/cron.d/laravel-scheduler
crontab /etc/cron.d/laravel-scheduler

# Start cron in the background
cron

# Run composer install (as per docker-compose command)
composer install --no-interaction --optimize-autoloader

# Start php-fpm in the foreground
php-fpm
