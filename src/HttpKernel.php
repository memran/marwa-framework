<?php

declare(strict_types=1);

namespace Marwa\Framework;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Adapters\Event\{AppBooted, AppTerminated};
use Marwa\Framework\Adapters\Http\RelayPipelineAdapter;
use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Bootstrappers\MiddlewareBootstrapper;
use Marwa\Framework\Bootstrappers\ProviderBootstrapper;
use Marwa\Framework\Config\AppConfig;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Contracts\MiddlewarePipelineInterface;
use Marwa\Framework\Middlewares\DebugbarMiddleware;
use Marwa\Framework\Middlewares\MaintenanceMiddleware;
use Marwa\Framework\Middlewares\RequestIdMiddleware;
use Marwa\Framework\Middlewares\RouterMiddleware;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Supports\Runtime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;

/**
 * HttpKernel orchestrates the HTTP lifecycle:
 * Relay (global middlewares) -> router dispatch -> response emission.
 */
final class HttpKernel
{
    private MiddlewarePipelineInterface $pipeline;

    public function __construct(
        private Application $app,
        private Config $config,
        private EventDispatcherInterface $events,
        private LoggerInterface $logger,
        private ProviderBootstrapper $providerBootstrapper,
        private MiddlewareBootstrapper $middlewareBootstrapper
    ) {
        $this->bootKernel();
    }

    private function bootKernel(): void
    {
        if (Runtime::isWeb() && env('APP_DEBUG', false)) {
            ErrorHandlerAdapter::boot();
        }

        $this->config->loadIfExists(AppConfig::KEY . '.php');

        /** @var array{providers:list<class-string>,middlewares:list<class-string>,debugbar:bool,collectors:list<string>} $appConfig */
        $appConfig = array_replace_recursive(AppConfig::defaults(), $this->config->getArray(AppConfig::KEY, []));

        $this->providerBootstrapper->bootstrap($appConfig['providers']);

        $this->pipeline = new RelayPipelineAdapter($this->app->container());
        $this->middlewareBootstrapper->bootstrap(
            $this->pipeline,
            $this->resolveMiddlewares($appConfig['middlewares'])
        );
    }

    /**
     * @param list<class-string> $configuredMiddlewares
     * @return list<MiddlewareInterface>
     */
    private function resolveMiddlewares(array $configuredMiddlewares): array
    {
        $middlewares = [];

        foreach ($configuredMiddlewares as $middleware) {
            if (class_exists($middleware)) {
                $middleware = $this->app->container()->get($middleware);
            }

            if ($middleware instanceof MiddlewareInterface) {
                $middlewares[] = $middleware;
                continue;
            }

            $this->logger->warning('Skipping invalid middleware registration.', [
                'middleware' => is_object($middleware) ? $middleware::class : get_debug_type($middleware),
            ]);
        }

        return $middlewares;
    }

    /**
     * @return list<class-string>
     */
    public static function defaultMiddlewares(): array
    {
        return [
            RequestIdMiddleware::class,
            MaintenanceMiddleware::class,
            RouterMiddleware::class,
            DebugbarMiddleware::class,
        ];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        debugger()?->mark('handle');

        $this->events->dispatch(new AppBooted(
            environment: (string) env('APP_ENV', 'production'),
            basePath: $this->app->basePath()
        ));

        return $this->pipeline->handle($request);
    }

    public function terminate(ResponseInterface $response): void
    {
        $this->events->dispatch(new AppTerminated(
            statusCode: $response->getStatusCode()
        ));

        (new SapiEmitter())->emit($response);
    }

    public function setNotFound(callable $handler): void
    {
        $router = $this->app->container()->get(RouterAdapter::class);

        if (!$router instanceof RouterAdapter) {
            $this->logger->warning('Router adapter is not available for not-found registration.', [
                'router' => is_object($router) ? $router::class : get_debug_type($router),
            ]);
            return;
        }

        $router->setNotFoundHandler($handler);
    }
}
