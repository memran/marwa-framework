<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Psr\Http\Server\MiddlewareInterface;

interface RouterInterface
{
    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function get(string $uri, callable|array $action): RouteInterface;

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function post(string $uri, callable|array $action): RouteInterface;

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function put(string $uri, callable|array $action): RouteInterface;

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function delete(string $uri, callable|array $action): RouteInterface;

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function patch(string $uri, callable|array $action): RouteInterface;

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function options(string $uri, callable|array $action): RouteInterface;

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function map(string $method, string $uri, callable|array $action): RouteInterface;

    /**
     * @param array<string, mixed> $attributes
     */
    public function group(array $attributes, callable $callback): void;

    /**
     * @param MiddlewareInterface|string|array<int, MiddlewareInterface|string> $middleware
     */
    public function middleware(MiddlewareInterface|string|array $middleware): self;
    public function name(string $name): self;
}
