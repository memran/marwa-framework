<?php

namespace Marwa\App\Contracts;

interface RouterInterface
{
    public function get(string $path, $handler): self;
    public function post(string $path, $handler): self;
    public function put(string $path, $handler): self;
    public function delete(string $path, $handler): self;
    public function patch(string $path, $handler): self;
    public function name(string $routeName): self;
    public function group(array $attributes, callable $callback): void;
    public function dispatch(\Psr\Http\Message\ServerRequestInterface $request);
}
