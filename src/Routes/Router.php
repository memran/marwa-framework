<?php

namespace Marwa\App\Routes;

use League\Route\Router as LeagueRouter;
use Marwa\App\Exceptions\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    /**
     * @var LeagueRouter
     */
    protected $router;
    /**
     * @var ServerRequestInterface $request
     */
    protected $request;


    /**
     * @var \League\Route\Route
     */
    protected $currentRoute;

    public function __construct(ServerRequestInterface $request)
    {
        $this->router = new LeagueRouter();
        $this->request = $request;
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
        $this->router->group($prefix, function ($routes) use ($callback) {
            $callback($this);
        });
    }

    public function dispatch()
    {
        return $this->router->dispatch($this->request);
    }

    public function getInternalRouter(): LeagueRouter
    {
        return $this->router;
    }
    /**
     * [__call description] magic method to call parent router
     * @param  [type] $method [description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function __call($method, $params)
    {

        if (method_exists($this->router, $method)) {
            $this->router->$method(...$params);
        } else {
            throw new NotFoundException('Sorry Method not Found');
        }
    }
}
