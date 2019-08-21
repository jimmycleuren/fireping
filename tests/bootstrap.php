<?php

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

require __DIR__.'/../config/bootstrap.php';