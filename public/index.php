<?php

require __DIR__.'/../bootstrap/app.php';

use App\Handlers\DatabaseHandler;

if (!DatabaseHandler::checkConnection() || !DatabaseHandler::isSchemaInstalled()) {
    echo 'Check readme for installation details.';
    die;
}

header('Location: /intervals');
die;
