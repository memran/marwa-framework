<?php

namespace Marwa\App\Core;

use League\Container\Container as LeagueContainer;
use League\Container\ReflectionContainer;
use Psr\Container\ContainerInterface;
use Marwa\App\Contracts\BindingInterface;
use Marwa\App\Exceptions\NotFoundException;

final class Container implements ContainerInterface, BindingInterface
{
    /**
     * Singleton container instance.
     *
     * @var static|null
     */
    protected static ?self $instance = null;

    /**
     * The underlying League container.
     *
     * @var LeagueContainer
     */
    protected LeagueContainer $container;

    /**
     * Mapping of tags to service IDs.
     *
     * @var array<string, array>
     */
    protected array $tags = [];

    /**
     * Constructor.
     */
    protected function __construct()
    {
        $this->container = new LeagueContainer();
        $this->container->delegate(new ReflectionContainer());
    }

    /**
     * Get singleton instance.
     */
    public static function getInstance(): static|null
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * PSR-compliant container access.
     */
    public function psr(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Bind a service.
     */
    public function bind(string $abstract, mixed $concrete = null): static
    {
        $this->container->add($abstract, $concrete);
        return $this;
    }

    /**
     * Bind a singleton.
     */
    public function singleton(string $abstract, mixed $concrete = null): static
    {
        $this->container->addShared($abstract, $concrete);
        return $this;
    }

    /**
     * Resolve a service.
     */
    public function make(string $abstract, ?bool $new = false): mixed
    {
        try {
            return $new
                ? $this->container->getNew($abstract)
                : $this->container->get($abstract);
        } catch (\Throwable $e) {
            throw new NotFoundException("Unable to resolve [$abstract]: {$e->getMessage()}");
        }
    }
    /**
     * Determine if container has binding.
     */
    public function get(string $abstract): mixed
    {
        if (!$this->container->has($abstract)) {
            throw new NotFoundException("Service [$abstract] not found in container.");
        }

        return $this->container->get($abstract);
    }

    /**
     * Determine if container has binding.
     */
    public function has(string $abstract): bool
    {
        return $this->container->has($abstract);
    }

    /**
     * Register a service provider.
     */
    public function register(string $providerClass): static
    {
        $this->container->addServiceProvider(new $providerClass());
        return $this;
    }

    /**
     * Tag one or more service IDs.
     */
    public function tag(array|string $ids, string|array $tags): static
    {
        $ids = (array)$ids;
        $tags = (array)$tags;

        foreach ($tags as $tag) {
            foreach ($ids as $id) {
                $this->tags[$tag][] = $id;
                $this->tags[$tag] = array_unique($this->tags[$tag]); // avoid duplicate
            }
        }

        return $this;
    }

    /**
     * Resolve all services for a tag.
     */
    public function tagged(string $tag): array
    {
        if (!isset($this->tags[$tag])) {
            return [];
        }

        return array_map(fn($id) => $this->make($id), $this->tags[$tag]);
    }

    /**
     * Proxy method calls to League container.
     */
    public function __call(string $method, array $parameters = []): mixed
    {

        return call_user_func_array([$this->container, $method], $parameters);
    }

    /**
     * Load League service providers from config['app']['providers'].
     */
    public function loadProviders(array $providers): void
    {

        $list = $providers ?? [];
        foreach ($list as $entry) {
            //$provider = self::resolveProvider($container, $entry);
            // Register with underlying League container
            $this->register($entry);
        }
    }
}
