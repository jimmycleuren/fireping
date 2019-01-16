FROM php:7.2.3-cli-stretch

RUN apt-get update
RUN apt-get install -y fping zip git rrdtool librrd-dev

RUN docker-php-ext-install pcntl
RUN pecl install rrd
RUN docker-php-ext-enable rrd

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '93b54496392c062774670ac18b134c3b3a95e5a5e5c8f1a9f115f203b75bf9a129d5daa8ba6a13e2cc8a1da0806388a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer

CMD ["php", "/app/bin/console", "app:probe:dispatcher", "--env=slave"]
