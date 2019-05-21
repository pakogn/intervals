<?php

require __DIR__.'/../../bootstrap/app.php';

use App\Service\Intervals\IntervalsManager;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    IntervalsManager::flush();

    $_SESSION['status'] = 'Intervals flushed successfuly.';

    header('Location: /intervals');
    die;
}
