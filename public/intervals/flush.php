<?php

require __DIR__.'/../../bootstrap/app.php';

use App\Service\Intervals\IntervalsManager;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    IntervalsManager::flush();

    $_SESSION['status'] = 'Interval deleted successfuly.';

    header('Location: /intervals');
    die;
}
