<?php

require __DIR__.'/../bootstrap/app.php';

use App\Handlers\DatabaseHandler;

if (!DatabaseHandler::checkConnection()) {
    echo 'Check your database connection configuration, please.';
    die;
}

DatabaseHandler::installSchema();

header('Location: /');
die;
