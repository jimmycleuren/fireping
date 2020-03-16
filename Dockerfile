FROM php:7.4-fpm

ENV MODE slave

ADD . /app

RUN apt-get update
RUN apt-get install -y fping zip git rrdtool librrd-dev procps php-mysql php-fpm

WORKDIR /app

RUN docker-php-ext-install pcntl pdo_mysql
RUN pecl install rrd
RUN docker-php-ext-enable rrd
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

CMD sh -c 'if [ "$MODE" = "master" ]; then ./docker-master.sh ; else ./docker-slave.sh ; fi'