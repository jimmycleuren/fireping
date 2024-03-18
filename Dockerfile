# Use an official, minimal base image for better security and size
FROM php:7.4.30-fpm-alpine

# Set environment variables
ENV MODE=slave
ENV DEV=false
ENV PHP_MEMORY_LIMIT=128M

# Create a non-root user for running the application
RUN adduser -D -H -u 1000 fireping

# Copy your application files into the container
COPY files/composer.json /app/composer.json

# Set the working directory
WORKDIR /app

# Copy configuration files (timezone and memory_limit)
COPY files/timezone.ini /usr/local/etc/php/conf.d/timezone.ini
COPY files/memory_limit.ini /usr/local/etc/php/conf.d/memory_limit.ini

# Change permissions for the configuration files
RUN chmod 644 /usr/local/etc/php/conf.d/timezone.ini
RUN chmod 644 /usr/local/etc/php/conf.d/memory_limit.ini

# Install necessary packages and remove cache to reduce image size
RUN apk --no-cache add make g++ zlib-dev pkgconfig autoconf fping zip git rrdtool-dev librrd && \
    apk --no-cache add --virtual .build-deps procps dos2unix && \
    docker-php-ext-install pcntl pdo_mysql && \
    pecl install rrd xdebug && \
    docker-php-ext-enable rrd && \
    php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer && \
    if [ "$DEV" = "true" ] ; then \
        composer install --verbose --prefer-dist --optimize-autoloader --no-scripts --no-suggest ; else \
        composer install --verbose --prefer-dist --no-dev --optimize-autoloader --no-scripts --no-suggest ; fi

ADD files/entrypoint.sh /usr/local/bin/

RUN dos2unix /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh && \
    apk del .build-deps && \
    rm -rf /var/cache/apk/*

# Drop root privileges for better security
# USER fireping

# Specify the entrypoint
ENTRYPOINT ["entrypoint.sh"]
