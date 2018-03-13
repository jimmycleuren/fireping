FROM php:7.2.3-cli-stretch

ADD . /app

RUN apt-get update
RUN apt-get install -y fping zip git

WORKDIR /app

RUN docker-php-ext-install pcntl
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
RUN composer install
CMD ["php", "/app/bin/console", "app:probe:dispatcher", "--env=slave"]