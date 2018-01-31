<?php

use DiskApp\Controller\FileController;
use DiskApp\Service\FileService;
use DiskApp\Repository\FileRepository;

use DiskApp\Controller\UserController;
use DiskApp\Service\UserService;
use DiskApp\Repository\UserRepository;

// User
$app['users.controller'] = function ($app) {
    return new UserController($app['users.service']);
};

$app['users.service'] = function ($app) {
    return new UserService($app['users.repository']);
};

$app['users.repository'] = function ($app) {
    return new UserRepository($app['db']);
};

// File
$app['files.controller'] = function ($app) {
    return new FileController($app['users.service'], $app['files.service']);
};

$app['files.service'] = function ($app) {
    return new FileService($app['users.repository'], $app['files.repository']);
};

$app['files.repository'] = function ($app) {
    return new FileRepository($app['db']);
};