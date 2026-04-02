<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Psr\Http\Server\MiddlewareInterface;

interface RouteInterface
{
    /**
     * @param MiddlewareInterface|string|array<int, MiddlewareInterface|string> $middleware
     */
    public function middleware(MiddlewareInterface|string|array $middleware): self;

    public function name(string $name): self;

    public function register(): self;
}
