#!/bin/bash

composer install
php /app/bin/console doctrine:migrations:migrate --no-interaction
php-fpm