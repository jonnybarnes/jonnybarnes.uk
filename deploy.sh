#!/bin/bash

echo "Putting the Laravel app in maintenance mode"
php artisan down

echo "Updating composer dependencies"
composer install

echo "Caching Laravel route and config files"
php artisan route:cache
php artisan config:cache

echo "Restarting the queue daemon"
sudo supervisorctl restart all

echo "Bringing the Laravel app back online"
php artisan up
