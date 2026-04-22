<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use League\Container\Container;
use Marwa\Router\RouterFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Router adapter over League\Route with fluent RouteInterface returns
 * to allow ->middleware() and ->name() chaining.
 */
final class RouterAdapter
{
    private RouterFactory $router;

    public function __construct(Container $container)
    {
        $this->router = new RouterFactory(container: $container);
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->dispatch($request);
    }

    public function setNotFoundHandler(callable $handler): self
    {
        $this->router->setNotFoundHandler($handler);

        return $this;
    }

    public function loadCompiledRoutesFrom(string $file): bool
    {
        return $this->router->loadCompiledRoutesFrom($file);
    }

    public function compileRoutesTo(string $file): void
    {
        $this->router->compileRoutesTo($file);
    }

    /**
     * @return array<int, array{methods:array<int,string>,path:string,name:?string,controller:?string,action:?string,domain:?string}>
     */
    public function routes(): array
    {
        return $this->router->routes();
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->router->fluent()->{$method}(...$arguments);
    }
}
