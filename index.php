<?php

require __DIR__.'/bootstrap/app.php';

use App\Handlers\DatabaseHandler;

if (!DatabaseHandler::checkConnection()) {
    header('Location: /install');
    die;
}

header('Location: /intervals');
die;
