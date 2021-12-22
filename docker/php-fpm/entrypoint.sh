#!/usr/bin/env bash

cd /var/www/app

# install composer dependencies
NO_DEV_DEPS="--no-dev"
if [[ "$DEBUG" == "true" ]]; then
	NO_DEV_DEPS=""
fi
composer install \
	--no-interaction \
	--no-plugins \
	--no-scripts \
	--prefer-dist \
	--optimize-autoloader \
	$NO_DEV_DEPS

# run migrations
composer migrate
composer clear-cakephp-cache

# set permissions to www-data
chown -R www-data:www-data .

echo "Finished bootstrapping the containers."
banner="
____ ____ ____ ____ ___  ____ ____ ___ ____ 
|    |___ |__/ |___ |__] |__/ |__|  |  |___ 
|___ |___ |  \ |___ |__] |  \ |  |  |  |___ 

"
echo "$banner"
echo "Starting php-fpm..."

exec $@