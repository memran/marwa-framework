<?php

declare(strict_types=1);

namespace Marwa\Framework;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Container\Container;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Adapters\Event\{AppBooted, AppTerminated};
use Marwa\Framework\Adapters\Http\RelayPipelineAdapter;
use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Contracts\MiddlewarePipelineInterface;
use Marwa\Framework\Facades\{Config, Event};
use Marwa\Framework\Middlewares\DebugbarMiddleware;
use Marwa\Framework\Middlewares\MaintenanceMiddleware;
use Marwa\Framework\Middlewares\RequestIdMiddleware;
use Marwa\Framework\Middlewares\RouterMiddleware;
use Marwa\Framework\Providers\KernalServiceProvider;
use Marwa\Framework\Supports\Runtime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * HttpKernel orchestrates the HTTP lifecycle:
 * Relay (global middlewares) → League\Route (group/per-route) → Controller → Response.
 */
final class HttpKernel
{
    /**
     * @var MiddlewarePipelineInterface
     */
    private MiddlewarePipelineInterface $pipeline;
    /**
     * @var Container
     *
     */
    private Container $container;

    public function __construct(
        Application $app
    ) {
        $this->container = $app->container();
        $this->bootKernel();
    }

    /**
     * Load kernel-related services.
     */
    private function bootKernel(): void
    {
        if (Runtime::isWeb() && env('APP_DEBUG', false)) {
            ErrorHandlerAdapter::boot();
        }

        Config::loadIfExists('app.php');

        $this->registerConfiguredProviders();

        $this->pipeline = new RelayPipelineAdapter($this->container);
        $this->registerMiddlewares();
    }

    /**
     * Register middleware configured by the consumer app or framework defaults.
     */
    private function registerMiddlewares(): void
    {
        $middlewares = Config::getArray('app.middlewares', [
            RequestIdMiddleware::class,
            MaintenanceMiddleware::class,
            RouterMiddleware::class,
            DebugbarMiddleware::class,
        ]);

        foreach ($middlewares as $middleware) {
            if (is_string($middleware) && class_exists($middleware)) {
                $middleware = $this->container->get($middleware);
            }

            if (!$middleware instanceof MiddlewareInterface) {
                logger()->warning('Skipping invalid middleware registration.', [
                    'middleware' => is_object($middleware) ? $middleware::class : get_debug_type($middleware),
                ]);
                continue;
            }

            $this->pipeline->push($middleware);
        }
    }

    /**
     * Register service providers from configuration or framework defaults.
     */
    private function registerConfiguredProviders(): void
    {
        $providers = Config::getArray('app.providers', [
            KernalServiceProvider::class,
        ]);

        foreach ($providers as $providerClass) {
            if (!is_string($providerClass) || !class_exists($providerClass)) {
                continue;
            }

            if (!is_subclass_of($providerClass, ServiceProviderInterface::class)) {
                logger()->warning('Skipping invalid service provider registration.', [
                    'provider' => $providerClass,
                ]);
                continue;
            }

            $this->container->addServiceProvider(new $providerClass());
        }
    }

    /**
     * Handle an incoming request and produce a response.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     *
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        debugger()?->mark('handle');

        Event::dispatch(new AppBooted(
            environment: env('APP_ENV', 'production'),
            basePath: app()->basePath()
        ));

        return $this->pipeline->handle($request);
    }

    /**
     * Terminate the request/response lifecycle.
     */
    public function terminate(ResponseInterface $response): void
    {

        Event::dispatch(new AppTerminated(
            statusCode: $response->getStatusCode()
        ));

        (new SapiEmitter())->emit($response);
    }

    public function setNotFound(callable $args): void
    {
        $this->container->get(RouterAdapter::class)->setNotFoundHandler($args);
    }
}
