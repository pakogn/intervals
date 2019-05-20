<?php

/*
| We may take advantage of Composer's class loader. With this We have
| the benefit to not manual load any of the classes the app use.
 */
require __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;

/*
| It's a good practice to have environment values in a centralized file,
| that's why We take advantage of Dotenv to store this values easily.
 */
$dotenv = Dotenv::create(__DIR__.'/..');
$dotenv->load();

/*
| Finally, We need to load a database manager to have an easy way
| to make database operations using a Modern Schema Builder.
 */
require __DIR__.'/../config/database.php';
