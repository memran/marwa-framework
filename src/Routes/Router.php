<?php

namespace Marwa\App\Routes;

use FastRoute\RouteCollector;
use League\Route\RouteCollection;
use League\Route\Router as LeagueRouter;
use Psr\Http\Message\ServerRequestInterface;
use Marwa\App\Contracts\RouterInterface;

class Router implements RouterInterface
{
    /**
     * @var LeagueRouter
     */
    protected $router;


    /**
     * @var \League\Route\Route
     */
    protected $currentRoute;

    public function __construct()
    {
        $this->router = new LeagueRouter();
    }

    public function get(string $path, $handler): self
    {
        $this->currentRoute = $this->router->map('GET', $path, $handler);
        return $this;
    }

    public function post(string $path, $handler): self
    {
        $this->currentRoute = $this->router->map('POST', $path, $handler);
        return $this;
    }

    public function put(string $path, $handler): self
    {
        $this->currentRoute = $this->router->map('PUT', $path, $handler);
        return $this;
    }

    public function delete(string $path, $handler): self
    {
        $this->currentRoute = $this->router->map('DELETE', $path, $handler);
        return $this;
    }

    public function patch(string $path, $handler): self
    {
        $this->currentRoute = $this->router->map('PATCH', $path, $handler);
        return $this;
    }

    public function name(string $routeName): self
    {
        if ($this->currentRoute) {
            $this->currentRoute->setName($routeName);
        }
        return $this;
    }

    public function group(array $attributes, callable $callback): void
    {
        $prefix = $attributes['prefix'] ?? '';
        $this->router->group($prefix, function (League\Route\RouteCollection $routes) use ($callback) {
            $callback($this);
        });
    }

    public function dispatch(ServerRequestInterface $request)
    {
        return $this->router->dispatch($request);
    }

    public function getInternalRouter(): LeagueRouter
    {
        return $this->router;
    }
}
