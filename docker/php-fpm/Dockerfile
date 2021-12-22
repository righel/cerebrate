ARG PHP_VERSION
FROM php:${PHP_VERSION}-fpm

# we need some extra libs to be installed in the runtime
RUN apt-get update && \
	apt-get install -y --no-install-recommends curl git zip unzip && \
	apt-get install -y --no-install-recommends libicu-dev libxml2-dev && \
	docker-php-ext-install intl pdo pdo_mysql mysqli simplexml

# install xdebug if requested
ARG DEV
ENV DEV=$DEV
RUN if [ "${DEV}" = "true" ]; then \
	pecl install xdebug && docker-php-ext-enable xdebug ; \
	fi

# keep it clean
RUN apt-get remove -y --purge libicu-dev libxml2-dev && \
	apt-get clean && \
	rm -rf /var/lib/apt/lists/*

# entrypoint
COPY docker/php-fpm/entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

# copy actual codebase
COPY --chown=www-data app /var/www/app
WORKDIR /var/www/app

# install composer
ARG COMPOSER_VERSION
RUN curl -sL https://getcomposer.org/installer | \
	php -- --install-dir=/usr/bin/ --filename=composer --version=${COMPOSER_VERSION}

# last checks with unprivileged user
USER www-data

# install composer dependencies
ARG DEV
RUN if [ "$DEV" = "true" ] ; then \
		composer install --no-interaction ; \
	else \ 
		composer install \
			--no-interaction \
			--no-plugins \
			--no-scripts \
			--no-progress \
			--prefer-dist \
			--optimize-autoloader \
			--no-dev ; \
	fi

# app config override making use of environment variables
COPY --chown=www-data docker/php-fpm/etc/app_local.php /var/www/app/config/app_local.php

# version 1.0 addition requires a config/config.json file
# can still be overriden by a docker volume
RUN cp -a /var/www/app/config/config.example.json /var/www/app/config/config.json

# run entrypoint as www-data
ENTRYPOINT [ "/entrypoint.sh" ]
