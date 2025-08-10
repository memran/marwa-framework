<?php

use Marwa\App\Facades\Response;
use Marwa\App\Facades\Router;

Router::get('/', function ($req) {

    return Response::json("It works", $req->all());
});
