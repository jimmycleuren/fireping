<?php

use Symfony\Component\Dotenv\Dotenv;

passthru(sprintf(
    'php "%s/../bin/console" cache:clear --env=test',
    __DIR__
));

passthru(sprintf(
    'php "%s/../bin/console" doctrine:schema:drop --env=test --force',
    __DIR__
));
passthru(sprintf(
    'php "%s/../bin/console" doctrine:schema:create --env=test',
    __DIR__
));
passthru(sprintf(
    'php "%s/../bin/console" doctrine:fixtures:load -n --env=test',
    __DIR__
));

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
