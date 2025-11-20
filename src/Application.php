<?php

declare(strict_types=1);

namespace Marwa\Framework;

use League\Container\Container;
use League\Container\ReflectionContainer;
use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Symfony\Component\Dotenv\Dotenv;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Marwa\Framework\Adapters\Logger\LoggerAdapter;

/**
 * Application bootstrapper: container, env, config, providers.
 * Keeps HTTP and Console concerns separate (handled by kernels).
 */
final class Application
{
    private Container $container;
    private string $basePath;
    private bool $booted = false;


    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->setAsGlobal();
        $this->bootContainer();
        $this->loadEnvironment();
        $this->bindAppSingletons();
    }

    // ---------------------------------------------------------------------
    // Bootstrapping
    // ---------------------------------------------------------------------
    private function bootContainer(): void
    {
        $this->container = new Container();
        // Reflection container enables auto-wiring of unbound concretes
        $this->container->delegate(new ReflectionContainer(true));
    }

    private function loadEnvironment(): void
    {
        $env = $this->basePath . '/.env';
        if (is_file($env)) {
            (new Dotenv())->load($env);
        }
    }

    /**
     * Bind core singletons into the container.
     */

    private function bindAppSingletons(): void
    {
        /**
         *  Set Timezone
         */
        date_default_timezone_set(env('TIMEZONE', 'Asia/Dhaka'));

        // Bind config repository (lazy loader)
        $this->container->addShared(Config::class)
            ->addArgument(config_path());
        /**
         * logger Singleton
         */
        $this->container->addShared(LoggerAdapter::class, function () {
            $logger = new LoggerAdapter();
            return $logger->getLogger();
        });

        /**
         * event Dispatcher
         */
        $this->container->addShared(EventDispatcherAdapter::class)
            ->addArgument($this->container());
    }


    // ---------------------------------------------------------------------
    // Lifecycle
    // ---------------------------------------------------------------------
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->container->get(EventDispatcherAdapter::class)->dispatch(new \Marwa\Framework\Adapters\Event\AppBooted(
            environment: env('APP_ENV', 'production'),
            basePath: $this->basePath
        ));
        $this->booted = true;
    }

    // ---------------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------------
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function setAsGlobal(): void
    {
        if (isset($GLOBALS['marwa_app']) && $GLOBALS['marwa_app'] instanceof Application) {
            return;
        }
        $GLOBALS['marwa_app'] = $this;
    }
    /**
     * Resolve a class or interface from the container.
     */
    public function make(string $abstract): mixed
    {
        if ($this->has($abstract)) {
            return $this->container->get($abstract);
        } else {
            $this->container->add($abstract);
            return $this->container->get($abstract);
        }
    }
    /**
     * Resolve a class or interface from the container.
     */
    public function singleton(string $abstract): mixed
    {
        if ($this->has($abstract)) {
            return $this->container->get($abstract);
        } else {
            $this->container->addShared($abstract);
            return $this->container->get($abstract);
        }
    }

    /**
     * Check if the container has a binding for the given class or interface.
     */
    public function has(string $abstract): bool
    {
        return $this->container->has($abstract);
    }

    /**
     * Get the underlying service container.
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Get the base path of the application.
     */
    public function basePath(string $path = ''): string
    {
        return $path ? $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $this->basePath;
    }
    /**
     * 
     */
    public function environment(string $env = ''): string|bool
    {
        if (!is_null($env)) {
            return env('APP_ENV') === $env ? true : false;
        }

        return env('APP_ENV');
    }
}
