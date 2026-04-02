<?php

use Carbon\Carbon;
use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/web', fn() => Response::json(['hello' => 'marwa']))->register();
Router::get('/', function () {
    $time = Carbon::now();
    $body = "<h1>Welcome to MarwaPHP</h1>.
        <br> 
        Current Time is Now: {$time}
        <hr>
    ";
    return Response::html($body);
})->name('hello')->register();

Router::get('/home', function () {
    return Response::html('<h1>Home</h1><p>MarwaPHP is ready.</p>');
})->name('home')->register();
