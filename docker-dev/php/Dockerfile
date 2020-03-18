FROM php:7.2-fpm

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
 && apt-get install -y \
      fping \
      zip \
      git \
      rrdtool \
      librrd-dev \
      zlib1g-dev \
      mariadb-client \
 && docker-php-ext-install \
      pdo_mysql \
      zip \
 && pecl install \
      xdebug \
      rrd \
 && docker-php-ext-enable \
      xdebug \
      rrd