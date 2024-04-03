FROM composer:2.7.1 AS composer
FROM php:8.2.17-fpm AS base

# Set environment variables
ENV MODE=slave
ENV DEV=false
ENV PHP_MEMORY_LIMIT="128M"

# Set user-related variables
ENV USER_ID=1000
ENV USER_NAME=fireping
ENV USER_GROUP=0

# Set working directory
WORKDIR /app

# Set user
RUN set -ex && useradd -u $USER_ID -g $USER_GROUP $USER_NAME --home /app

# Copy app to working directory
COPY --chown=$USER_NAME:$USER_GROUP bin/console /app/bin/console
COPY --chown=$USER_NAME:$USER_GROUP config/ /app/config
COPY --chown=$USER_NAME:$USER_GROUP migrations/ /app/migrations
COPY --chown=$USER_NAME:$USER_GROUP src/ /app/src
COPY --chown=$USER_NAME:$USER_GROUP public/ /app/public
COPY --chown=$USER_NAME:$USER_GROUP templates/ /app/templates
COPY --chown=$USER_NAME:$USER_GROUP LICENSE .env composer.json composer.lock symfony.lock /app/

# Copy PHP configuration files
COPY --chown=$USER_NAME:$USER_GROUP docker/timezone.ini docker/memory_limit.ini /usr/local/etc/php/conf.d/

# Copy Composer files
COPY --chown=$USER_NAME:$USER_GROUP --from=composer /usr/bin/composer /usr/bin/composer

# Copy entrypoint script
COPY --chown=$USER_NAME:$USER_GROUP docker/entrypoint.sh /usr/local/bin/

RUN chmod 755 /usr/local/etc/php/conf.d/timezone.ini \
    && chmod 755 /usr/local/etc/php/conf.d/memory_limit.ini \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && apt-get update \
    && apt-get install --no-install-recommends -y fping unzip libzip-dev git rrdtool librrd-dev procps dos2unix \
    && apt-get purge \
    && rm -rf /var/lib/apt/lists/* \
    && dos2unix /usr/local/bin/entrypoint.sh \
    && docker-php-ext-install zip pcntl pdo_mysql \
    && pecl install rrd \
    && docker-php-ext-enable rrd \
    && if [ "$DEV" = "true" ] ; then \
        composer install --no-ansi --no-interaction --no-progress --verbose --prefer-dist --optimize-autoloader --no-scripts ; \
       else \
        composer install --no-ansi --no-interaction --no-progress --verbose --prefer-dist --no-dev --optimize-autoloader --no-scripts ; \
       fi \
    && composer clear-cache \
    && rm -rf /usr/bin/composer /root/.composer \
    && mkdir -p /app/var/cache/slave \
    && chown -R $USER_NAME:$USER_GROUP /app/

# Switch user
USER $USER_ID

ENTRYPOINT ["entrypoint.sh"]
