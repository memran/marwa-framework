<?php

declare(strict_types=1);

namespace Marwa\Framework;

use Marwa\Framework\Contracts\MiddlewarePipelineInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Marwa\Framework\Adapters\Http\RelayPipelineAdapter;
use League\Container\Container;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Server\MiddlewareInterface;
use Marwa\Framework\Facades\{Config, Event};
use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Supports\Runtime;

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

    protected $notFoundHandler;


    // private ?EventDispatcherInterface $events = null;
    public function __construct(
        Application $app
    ) {
        $this->container = $app->container();
        $this->bootKernal();
    }

    /**
     * Load All Kernal Related Service
     */
    private function bootKernal(): void
    {
        /**
         * Enable Error handler
         */
        if (Runtime::isWeb()) {
            if (env('APP_DEBUG', false)) {
                ErrorHandlerAdapter::boot();
            }
        }
        /**
         * Load Default app.php configuration
         */
        Config::load('app.php');

        /** 
         * Register All Service Providers
         */
        $this->registerConfiguredProviders();

        $this->pipeline = new RelayPipelineAdapter($this->container);
        $this->registerMiddlewares();
    }

    /**
     * Middlware Registration from config files
     */
    private function registerMiddlewares(): void
    {
        $middlewares = Config::getArray('app.middlewares');

        foreach ($middlewares as $mwClass) {
            if ($mwClass instanceof MiddlewareInterface) {
                logger()->debug("class is not instance of MiddlewareInterface");
                continue;
            }
            $this->pipeline->push($mwClass);
        }
    }
    /**
     * register service provider from app.php files
     */
    private function registerConfiguredProviders(): void
    {
        $providers = Config::getArray('app.providers');

        // HTTP routes provider may be added by the consumer app
        foreach ($providers as $providerClass) {
            if (!class_exists($providerClass)) {
                continue;
            }
            if ($providerClass instanceof ServiceProviderInterface) {
                continue;
            }

            $this->container->addServiceProvider(new $providerClass);
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
        // Optional lifecycle event
        Event::dispatch(new \Marwa\Framework\Adapters\Event\AppBooted(
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

        Event::dispatch(new \Marwa\Framework\Adapters\Event\AppTerminated(
            statusCode: $response->getStatusCode()
        ));

        (new SapiEmitter())->emit($response);
    }

    public function setNotFound(callable $args): void
    {
        $this->notFoundHandler = $args;
    }
}
