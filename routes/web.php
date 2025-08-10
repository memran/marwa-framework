<?php

use Marwa\App\Facades\Response;
use Marwa\App\Facades\Router;

Router::get('/', function ($req) {
    return Response::html("It works");
});

Router::get('/edit/{id}', function ($req, $args) {
    return Response::html("It works {$args['id']}");
});


Router::get('/save/{id}', function ($req, $args) {
    return Response::html("It works with request class {$req->input('id')}");
});
