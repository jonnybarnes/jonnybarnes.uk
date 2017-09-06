#!/bin/bash

echo "Putting the Laravel app in maintenance mode"
php artisan down

echo "Pulling the latest changes"
git pull

echo "Updating composer and dependencies"
sudo composer self-update
composer install --no-dev

echo "Caching Laravel route and config files"
php artisan route:cache
php artisan config:cache

echo "Restarting the queue daemon"
sudo supervisorctl restart all

echo "Bringing the Laravel app back online"
php artisan up
