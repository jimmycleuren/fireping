FROM php:8.0.30-fpm

ENV MODE slave
ENV DEV false
ENV PHP_MEMORY_LIMIT="128M"

ADD . /app

RUN apt-get update
RUN apt-get install -y fping zip git rrdtool librrd-dev procps dos2unix

WORKDIR /app

COPY docker/timezone.ini /usr/local/etc/php/conf.d/timezone.ini
COPY docker/memory_limit.ini /usr/local/etc/php/conf.d/memory_limit.ini
RUN chmod 755 /usr/local/etc/php/conf.d/timezone.ini \
    && chmod 755 /usr/local/etc/php/conf.d/memory_limit.ini

RUN docker-php-ext-install pcntl pdo_mysql
RUN pecl install rrd
RUN docker-php-ext-enable rrd
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

RUN if [ "$DEV" = "true" ] ; then \
    composer install --verbose --prefer-dist --optimize-autoloader --no-scripts --no-suggest ; else \
    composer install --verbose --prefer-dist --no-dev --optimize-autoloader --no-scripts --no-suggest ; fi

ADD docker/entrypoint.sh /usr/local/bin/
RUN dos2unix /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
