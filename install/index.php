<?php

require __DIR__.'/../bootstrap/app.php';

use App\Handlers\DatabaseHandler;

if (!DatabaseHandler::checkConnection()) {
    echo 'Check your database connection configuration, please.'.PHP_EOL;
    die;
}

DatabaseHandler::installSchema();

echo 'Database ready.'.PHP_EOL;
die;
