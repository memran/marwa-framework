<?php

use Marwa\App\Facades\{Response, Event, Storage};
use Marwa\App\Facades\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CanViewMiddleware;

use App\Events\Event\UserRegistered;


Router::get('/', function ($req) {
    return view('index', ['name' => 'Marwa']);
    //return Response::html("It works");
})->name('home');

Router::get("/events", function () {

    event()->listen(UserRegistered::class, function (object $event) {
        logger()->debug('calling from callable event' . $event->id);
    });
    //Storage::disk()->put("storage", "hello world");
    // Dispatch a class-based event (preferred)
    $event = event()->dispatch(new UserRegistered(42, 'jane@example.com'));
    //$event = event()->dispatch(new UserRegistered(42, 'jane@example.com'));
    //$event = Event::dispatch(new UserRegistered(42, 'jane@example.com'));
    //logger()->debug("From Controller ID#" . $event->id);
    // Dispatch a string event name (also supported)
    //$event = Event::dispatch('order.paid', ['order_id' => 1234]);

    return Response::json(['events', 'Succesfully done!', $event]);
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
