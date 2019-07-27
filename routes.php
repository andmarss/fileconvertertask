<?php

use App\System\Router;

Router::get('/', 'IndexController@index');

Router::post('/upload', 'IndexController@upload');