<?php

use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../src/services.php';
require __DIR__ . '/../src/routes.php';

$app->register(new DoctrineServiceProvider(), [
    'db.options' => [
        'driver' => 'pdo_mysql',
        'host' => '127.0.0.1',
        'dbname' => 'disk_app',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8'
    ]
]);

$app->register(new Silex\Provider\ServiceControllerServiceProvider());