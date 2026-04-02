<?php

declare(strict_types=1);

namespace Marwa\Framework;

use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Marwa\Framework\Adapters\Event\ApplicationStarted;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Marwa\Framework\Bootstrappers\CoreBindingsBootstrapper;
use Marwa\Framework\Console\CommandRegistry;
use Marwa\Framework\Console\ConsoleKernel;
use Marwa\Module\Contracts\ModuleRegistryInterface;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;
use Marwa\Module\ModuleBuilder;
use Marwa\Module\ModuleHandle;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Application bootstrapper: container, env, config, providers.
 * Keeps HTTP and Console concerns separate (handled by kernels).
 */
final class Application
{
    private Container $container;
    private string $basePath;
    private bool $booted = false;

    /**
     * @var array<string, ModuleServiceProviderInterface>
     */
    private array $moduleServiceProviders = [];

    /**
     * @var array<string, true>
     */
    private array $bootedModuleServiceProviders = [];

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
        (new CoreBindingsBootstrapper())->bootstrap($this, $this->container);
        $this->dispatch(new ApplicationStarted(
            environment: (string) env('APP_ENV', 'production'),
            basePath: $this->basePath
        ));
    }

    // ---------------------------------------------------------------------
    // Lifecycle
    // ---------------------------------------------------------------------
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->dispatch(new \Marwa\Framework\Adapters\Event\AppBooted(
            environment: env('APP_ENV', 'production'),
            basePath: $this->basePath
        ));
        $this->booted = true;
    }

    public function dispatch(object $event): object
    {
        return $this->container->get(EventDispatcherAdapter::class)->dispatch($event);
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

    public function environment(?string $env = null): string|bool|null
    {
        if ($env !== null) {
            return env('APP_ENV') === $env;
        }

        return env('APP_ENV');
    }

    public function registerCommand(object|string $command): void
    {
        $this->container->get(CommandRegistry::class)->register($command);
    }

    /**
     * @param iterable<object|string> $commands
     */
    public function registerCommands(iterable $commands): void
    {
        $this->container->get(CommandRegistry::class)->registerMany($commands);
    }

    public function add(string $id, mixed $value): void
    {
        $this->container->addShared($id, $value);
    }

    public function set(string $id, mixed $value): void
    {
        $this->add($id, $value);
    }

    public function addServiceProvider(string|ServiceProviderInterface|ModuleServiceProviderInterface $provider): void
    {
        if (is_string($provider)) {
            if (!class_exists($provider)) {
                throw new \InvalidArgumentException(sprintf('Service provider [%s] does not exist.', $provider));
            }

            $provider = new $provider();
        }

        if ($provider instanceof ServiceProviderInterface) {
            $this->container->addServiceProvider($provider);

            return;
        }

        if ($provider instanceof ModuleServiceProviderInterface) {
            $class = $provider::class;

            if (isset($this->moduleServiceProviders[$class])) {
                return;
            }

            if (method_exists($provider, 'setContainer')) {
                $provider->setContainer($this->container);
            }

            $provider->register($this);
            $this->moduleServiceProviders[$class] = $provider;

            return;
        }

        throw new \InvalidArgumentException(sprintf(
            'Service provider [%s] must implement %s or %s.',
            $provider::class,
            ServiceProviderInterface::class,
            ModuleServiceProviderInterface::class
        ));
    }

    public function bootModuleServiceProviders(): void
    {
        foreach ($this->moduleServiceProviders as $class => $provider) {
            if (isset($this->bootedModuleServiceProviders[$class])) {
                continue;
            }

            $provider->boot($this);
            $this->bootedModuleServiceProviders[$class] = true;
        }
    }

    public function console(): ConsoleKernel
    {
        return $this->container->get(ConsoleKernel::class);
    }

    /**
     * @return array<string, \Marwa\Module\Module>
     */
    public function modules(): array
    {
        if (!$this->has(ModuleRegistryInterface::class)) {
            return [];
        }

        /** @var ModuleRegistryInterface $registry */
        $registry = $this->make(ModuleRegistryInterface::class);

        return $registry->all();
    }

    public function hasModule(string $slug): bool
    {
        if (!$this->has(ModuleBuilder::class)) {
            return false;
        }

        /** @var ModuleBuilder $builder */
        $builder = $this->make(ModuleBuilder::class);

        return $builder->has($slug);
    }

    public function module(string $slug): ModuleHandle
    {
        /** @var ModuleBuilder $builder */
        $builder = $this->make(ModuleBuilder::class);

        return $builder->current($slug);
    }
}
