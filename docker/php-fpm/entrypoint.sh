#!/usr/bin/env bash

cd /var/www/app

# run migrations
composer migrate

# clear
composer clear-cakephp-cache

echo "Finished bootstrapping the containers."
banner="
____ ____ ____ ____ ___  ____ ____ ___ ____ 
|    |___ |__/ |___ |__] |__/ |__|  |  |___ 
|___ |___ |  \ |___ |__] |  \ |  |  |  |___ 

"
echo "$banner"
echo "Starting php-fpm..."

exec $@