<?php

use Marwa\App\Facades\Response;
use Marwa\App\Facades\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CanViewMiddleware;
use App\Controllers\HomeController;


Router::get('/', function ($req) {

    return view('welcome', ['name' => 'Marwa']);
    //return Response::html("It works");
})->name('home');

Router::get('/edit/{id}', function ($req, $args) {
    return Response::html("It works {$args['id']}");
})->middleware(new AuthMiddleware());


Router::get('/save/{id}', function ($req, $args) {
    return Response::html("It works with request class {$req->input('id')}");
});

// Group middleware + per-route middleware combined
Router::group(['prefix' => '/admin', 'middleware' => [new AuthMiddleware()]], function () {
    Router::get('/dashboard', 'App\Controllers\HomeController::index')
        ->middleware(new CanViewMiddleware())
        ->name('admin.dashboard');
});
