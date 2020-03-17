FROM php:7.4-fpm

ENV MODE slave

ADD . /app

RUN apt-get update
RUN apt-get install -y fping zip git rrdtool librrd-dev

WORKDIR /app

RUN docker-php-ext-install pcntl pdo_mysql
RUN pecl install rrd
RUN docker-php-ext-enable rrd
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

CMD SYMFONY_ENV=$MODE composer install
CMD ["php", "/app/bin/console", "app:probe:dispatcher", "--env=slave"]
