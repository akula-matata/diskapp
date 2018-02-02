<?php

$routes = $app['controllers_factory'];

$routes->post('/register', 'users.controller:register');

$routes->get('/files', 'files.controller:getFilesList');
$routes->get('/files/{filename}', 'files.controller:getFile');
$routes->post('/files/put', 'files.controller:putFile');
$routes->post('/files/delete/{filename}', 'files.controller:deleteFile');
$routes->post('/files/update', 'files.controller:updateFile');
$routes->get('/metadata/{filename}', 'files.controller:getFileMetadata');

$app->mount('/', $routes);