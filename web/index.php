<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

// ... definitions
require __DIR__ . '/../src/app.php';

$app['debug'] = true;

$app->run();