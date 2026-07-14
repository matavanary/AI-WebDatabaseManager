<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;

$app = new Application(dirname(__DIR__));

// Load routes
require_once __DIR__ . '/../app/Routes/web.php';

$app->run();
