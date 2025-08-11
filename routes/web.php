<?php

use Marwa\App\Facades\Response;
use Marwa\App\Facades\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CanViewMiddleware;
use Marwa\App\Events\EventManager;
use App\Events\Event\UserRegistered;



Router::get('/', function ($req) {
    return view('index', ['name' => 'Marwa']);
    //return Response::html("It works");
})->name('home');

Router::get("/events", function () {
    //Event::dispatch(new UserRegistered(userId: 42, email: 'alice@example.com'));
    /** @var EventManager $events */
    $events = app(EventManager::class);


    // Dispatch a class-based event (preferred)
    $events->dispatch(new UserRegistered(42, 'jane@example.com'));

    // Dispatch a string event name (also supported)
    $events->dispatch('order.paid', ['order_id' => 1234]);
    return Response::json(['events', 'Succesfully done!']);
});

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
