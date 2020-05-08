FROM php:7.4-cli

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
 && apt-get install -y \
      fping \
      zip \
      git \
      rrdtool \
      librrd-dev \
      libzip-dev \
      procps \
 && docker-php-ext-install pcntl \
 && pecl install rrd \
 && docker-php-ext-enable rrd

COPY timezone.ini /usr/local/etc/php/conf.d/timezone.ini
RUN chmod 755 /usr/local/etc/php/conf.d/timezone.ini

CMD ["php", "/app/bin/console", "app:probe:dispatcher", "--env=slave"]
