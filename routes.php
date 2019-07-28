<?php

use App\System\Router;

$router->get('/', 'IndexController@index')->name('index');

$router->get('/download/{path}', 'IndexController@download')
    ->where(['path' => '.+'])
    ->name('download');

$router->post('/upload', 'IndexController@upload')->name('upload');