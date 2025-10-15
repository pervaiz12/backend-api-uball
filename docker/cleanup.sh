#!/bin/sh

# Remove Pail service provider from config/app.php
if [ -f /var/www/html/config/app.php ]; then
    sed -i "/Laravel\\Pail\\PailServiceProvider/d" /var/www/html/config/app.php
fi

# Remove any cached configuration
rm -f /var/www/html/bootstrap/cache/config.php

# Generate application key if not exists
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
    php /var/www/html/artisan key:generate --force
fi
