<?php

require 'vendor/autoload.php';

require 'bootstrap.php';

use App\System\{Request, Router};

Router::load('routes.php')
    ->direct(Request::uri(), Request::method());