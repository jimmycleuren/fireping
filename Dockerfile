FROM php:7.4.3-cli

ADD . /app

RUN apt-get update
RUN apt-get install -y fping zip git rrdtool librrd-dev php-mysql php-fpm

WORKDIR /app

RUN docker-php-ext-install pcntl
RUN pecl install rrd
RUN docker-php-ext-enable rrd
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
RUN SYMFONY_ENV=slave composer install
CMD ["php", "/app/bin/console", "app:probe:dispatcher", "--env=slave"]
