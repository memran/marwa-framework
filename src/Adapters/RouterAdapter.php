<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use League\Container\Container;
use Marwa\Framework\Contracts\{RouterInterface, RouteInterface};
use Marwa\Router\RouterFactory;
use Marwa\Router\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Router adapter over League\Route with fluent RouteInterface returns
 * to allow ->middleware() and ->name() chaining.
 */
final class RouterAdapter
{
    private RouterFactory $router;
    public function __construct(
        private Container $container
    ) {
        $this->router = new RouterFactory(container: $container);
        // $this->router->fluent()->get('/', function () {
        //     return Response::html('Ok');
        // })->name('hello')->register();
    }

    public function dispatch(RequestInterface $request): ResponseInterface
    {
        return $this->router->dispatch($request);
    }

    public function __call(string $method, $arguments)
    {
        return call_user_func_array([$this->router->fluent(), $method], $arguments);
    }
}
