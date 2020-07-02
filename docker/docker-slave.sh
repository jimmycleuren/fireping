#!/bin/bash

SYMFONY_ENV=slave composer install
php /app/bin/console app:probe:dispatcher --env=slave