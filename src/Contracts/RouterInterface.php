<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Psr\Http\Server\MiddlewareInterface;

interface RouterInterface
{
    public function get(string $uri, callable|array $action): RouteInterface;
    public function post(string $uri, callable|array $action): RouteInterface;
    public function put(string $uri, callable|array $action): RouteInterface;
    public function delete(string $uri, callable|array $action): RouteInterface;
    public function patch(string $uri, callable|array $action): RouteInterface;
    public function options(string $uri, callable|array $action): RouteInterface;
    public function map(string $method, string $uri, callable|array $action): RouteInterface;

    public function group(array $attributes, callable $callback): void;
    public function middleware(MiddlewareInterface|string|array $middleware): self;
    public function name(string $name): self;
}
