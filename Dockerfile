FROM php:7.3.19-cli-stretch

ADD . /app

RUN apt-get update
RUN apt-get install -y fping zip git rrdtool librrd-dev procps

WORKDIR /app

RUN docker-php-ext-install pcntl
RUN pecl install rrd
RUN docker-php-ext-enable rrd
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
RUN APP_ENV=slave composer install
CMD ["php", "/app/bin/console", "app:probe:dispatcher", "--env=slave"]
