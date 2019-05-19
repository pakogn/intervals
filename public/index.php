<?php

require __DIR__.'/../bootstrap/app.php';

use App\Handlers\DatabaseHandler;

if (!DatabaseHandler::checkConnection()) {
    echo 'Check readme for installation details.';
    die;
}

header('Location: /intervals');
die;
