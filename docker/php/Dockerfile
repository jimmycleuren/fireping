FROM php:7.4-fpm

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
      libzip-dev \
 && docker-php-ext-install \
      pdo_mysql \
      zip \
 && pecl install \
      xdebug \
      rrd \
 && docker-php-ext-enable \
      xdebug \
      rrd

RUN sed -i "s|;*listen\s*=\s*9000|listen = 0.0.0.0:9000|g" /usr/local/etc/php-fpm.d/zz-docker.conf

COPY timezone.ini /usr/local/etc/php/conf.d/timezone.ini
RUN chmod 755 /usr/local/etc/php/conf.d/timezone.ini

#RUN php /app/bin/console doctrine:migrations:migrate -n
#RUN php bin/console fos:user:create admin admin@fireping.be admin --super-admin