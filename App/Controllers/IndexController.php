<?php

namespace App\Controllers;

use App\System\Request;
use App\System\Router;

class IndexController
{
    public function index(Request $request)
    {
        return view('index');
    }

    public function upload(Request $request)
    {

    }

    public function download(Request $request)
    {

    }
}