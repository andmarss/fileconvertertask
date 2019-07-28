<?php

use App\System\Router;

Router::get('/', 'IndexController@index')->name('index');

Router::get('/download/{path}', 'IndexController@download')
    ->where(['path' => '.+'])
    ->name('download');

Router::post('/upload', 'IndexController@upload')->name('upload');