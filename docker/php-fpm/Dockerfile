ARG PHP_VERSION
FROM php:${PHP_VERSION}-fpm

# we need some extra libs to be installed in the runtime
RUN apt-get update && \
	apt-get install -y --no-install-recommends curl git zip unzip && \
	apt-get install -y --no-install-recommends libicu-dev libxml2-dev && \
	docker-php-ext-install intl pdo pdo_mysql mysqli simplexml

# install xdebug if requested
ARG DEBUG
ENV DEBUG=$DEBUG
RUN if [ "${DEBUG}" = "true" ]; then \
	pecl install xdebug && docker-php-ext-enable xdebug ; \
	fi

# keep it clean
RUN apt-get remove -y --purge libicu-dev libxml2-dev && \
	apt-get clean && \
	rm -rf /var/lib/apt/lists/*

# entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

# install composer
ARG COMPOSER_VERSION
RUN curl -sL https://getcomposer.org/installer | \
	php -- --install-dir=/usr/bin/ --filename=composer --version=${COMPOSER_VERSION}

ENTRYPOINT [ "/entrypoint.sh" ]
