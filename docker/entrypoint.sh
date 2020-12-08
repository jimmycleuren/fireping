#!/bin/bash

ls -p /usr/local/share/ca-certificates/

if [ $(ls -p /usr/local/share/ca-certificates/ | grep ".crt$") ]; then
  update-ca-certificates --verbose
fi

if [ "$MODE" = "master" ]
then
  php /app/bin/console cache:warmup
  php /app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
  php-fpm
else
  php /app/bin/console cache:warmup --env=slave
  php /app/bin/console app:probe:dispatcher --env=slave
fi