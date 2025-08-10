<?php

declare(strict_types=1);

namespace Marwa\App\Routes;

use League\Route\Router as LeagueRouter;
use League\Route\Strategy\ApplicationStrategy;
use League\Route\RouteCollectionInterface;
use League\Route\Route;
use League\Route\RouteGroup;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Router adapter over League\Route that preserves the Marwa router abstraction.
 * - Container-aware (PSR-11) via ApplicationStrategy for "Controller@method" resolution.
 * - Supports global, group, and per-route middleware without changing the public abstraction.
 */
final class Router
{
    /** @var LeagueRouter Underlying League router */
    private LeagueRouter $router;

    /** @var Route|null Last mapped route for chaining ->name() */
    private ?Route $currentRoute = null;

    /** @var RouteCollectionInterface|null Current group collection when inside group() */
    private ?RouteCollectionInterface $activeCollection = null;
    private ServerRequestInterface $request;

    /**
     * Create router with optional container and global middleware.
     *
     * @param ContainerInterface|null $container        PSR-11 container to resolve controllers & middleware
     * @param array<int,string|object> $globalMiddleware List of middleware class-names or instances
     */
    public function __construct(ContainerInterface $container, ServerRequestInterface $request, array $globalMiddleware = [])
    {
        $this->router = new LeagueRouter();
        $this->request = $request;
        // Use ApplicationStrategy so "Controller@method" resolves through container
        $strategy = new ApplicationStrategy();
        if ($container) {
            $strategy->setContainer($container);
        }
        $this->router->setStrategy($strategy);

        // Attach global middleware, if any
        foreach ($globalMiddleware as $mw) {
            $this->router->middleware($mw);
        }
    }

    /**
     * Map a GET route.
     *
     * @param string $path
     * @param mixed  $handler  callable|string|array{uses:mixed,middleware?:array}
     * @return self
     */
    public function get(string $path, $handler): self
    {
        $this->currentRoute = $this->map('GET', $path, $handler);
        return $this;
    }

    /**
     * Map a POST route.
     *
     * @param string $path
     * @param mixed  $handler
     * @return self
     */
    public function post(string $path, $handler): self
    {
        $this->currentRoute = $this->map('POST', $path, $handler);
        return $this;
    }

    /**
     * Map a PUT route.
     *
     * @param string $path
     * @param mixed  $handler
     * @return self
     */
    public function put(string $path, $handler): self
    {
        $this->currentRoute = $this->map('PUT', $path, $handler);
        return $this;
    }

    /**
     * Map a DELETE route.
     *
     * @param string $path
     * @param mixed  $handler
     * @return self
     */
    public function delete(string $path, $handler): self
    {
        $this->currentRoute = $this->map('DELETE', $path, $handler);
        return $this;
    }

    /**
     * Map a PATCH route.
     *
     * @param string $path
     * @param mixed  $handler
     * @return self
     */
    public function patch(string $path, $handler): self
    {
        $this->currentRoute = $this->map('PATCH', $path, $handler);
        return $this;
    }

    /**
     * Set route name for the last mapped route (Laravel-style chaining).
     *
     * @param string $routeName
     * @return self
     */
    public function name(string $routeName): self
    {
        if ($this->currentRoute instanceof Route) {
            $this->currentRoute->setName($routeName);
        }
        return $this;
    }

    /**
     * Define a group of routes with optional prefix and middleware.
     * Example:
     *  Route::group(['prefix' => '/admin', 'middleware' => [Auth::class]], function() {
     *      Route::get('/dashboard', 'AdminController@dashboard')->name('admin.dashboard');
     *  });
     *
     * @param array<string,mixed> $attributes  Supported: prefix, middleware(array)
     * @param callable            $callback    Will be invoked to add routes within this group
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        $prefix     = (string)($attributes['prefix'] ?? '');
        $middleware = (array)($attributes['middleware'] ?? []);

        $this->router->group($prefix, function (RouteGroup $group) use ($callback, $middleware): void {
            // attach group middleware first (class names or instances)
            foreach ($middleware as $mw) {
                $group->middleware($mw);
            }

            // within the group, map routes against the group's collection
            $prev = $this->activeCollection;
            $this->activeCollection = $group;

            try {
                $callback($this); // user calls ->get(), ->post(), etc.
            } finally {
                $this->activeCollection = $prev;
            }
        });
    }

    /**
     * Dispatch the incoming PSR-7 request and return a PSR-7 response.
     *
     * @param ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatch(): ResponseInterface
    {

        return $this->router->dispatch($this->request);
    }

    /**
     * Expose the underlying League router if you need advanced features.
     *
     * @return LeagueRouter
     */
    public function raw(): LeagueRouter
    {
        return $this->router;
    }

    /**
     * Internal helper to map a method/path/handler honoring groups and per-route middleware.
     *
     * @param string $method
     * @param string $path
     * @param mixed  $handler  callable|string|array{uses:mixed,middleware?:array}
     * @return Route
     */
    private function map(string $method, string $path, $handler): Route
    {
        // Normalize Laravel-style handler arrays: ['uses' => ..., 'middleware' => [...]]
        $uses       = $handler;
        $mwForRoute = [];

        if (is_array($handler) && array_key_exists('uses', $handler)) {
            $uses       = $handler['uses'];
            $mwForRoute = (array)($handler['middleware'] ?? []);
        }

        // Map route either on active group or on root router
        $route = $this->activeCollection instanceof RouteCollectionInterface
            ? $this->activeCollection->map($method, $path, $uses)
            :  $this->router->map($method, $path, $uses);

        // Attach per-route middleware if provided
        foreach ($mwForRoute as $mw) {
            $route->middleware($mw);
        }

        return $route;
    }

    public function getRouter(): mixed
    {
        return $this;
    }
}
