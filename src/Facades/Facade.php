<?php

declare(strict_types=1);

namespace Marwa\App\Facades;

use Marwa\App\Exceptions\FacadeException;
use Psr\Container\ContainerInterface;

/**
 * Base Facade to provide static proxy access to services.
 *
 * - Resolves instances from a PSR-11 container (preferred).
 * - Caches resolved instances per alias for performance.
 * - Allows swapping instances (useful for testing).
 */
abstract class Facade
{
    /**
     * Cached resolved instances keyed by alias.
     *
     * @var array<string,object>
     */
    protected static array $resolvedInstance = [];

    /**
     * Global container for facades (set by bootstrap).
     */
    protected static ?ContainerInterface $container = null;

    /**
     * Return the alias (container id or class FQCN) for the underlying component.
     */
    abstract protected static function getClassAlias(): string;

    /**
     * Set the container used by all facades.
     */
    public static function setContainer(ContainerInterface $container): void
    {
        static::$container = $container;
    }



    /**
     * Clear the cached instance for a specific alias (or all if null).
     */
    public static function clearResolvedInstance(?string $alias = null): void
    {
        if ($alias === null) {
            static::$resolvedInstance = [];
            return;
        }
        unset(static::$resolvedInstance[$alias]);
    }

    /**
     * Swap the resolved instance for a given alias (useful in tests).
     */
    public static function swap(object $instance): void
    {
        $alias = static::getAliasOrFail();
        static::$resolvedInstance[$alias] = $instance;
    }

    /**
     * Resolve the underlying instance from cache, container, or FQCN.
     */
    protected static function getInstance(): object
    {
        $alias = static::getAliasOrFail();
        // 1) Cache
        if (isset(static::$resolvedInstance[$alias])) {
            return static::$resolvedInstance[$alias];
        }

        // 2) Container (preferred)
        if (static::$container instanceof ContainerInterface && static::$container->has($alias)) {
            $resolved = static::$container->get($alias);
            if (!\is_object($resolved)) {
                throw new FacadeException("Container returned non-object for alias '{$alias}'.");
            }
            return static::$resolvedInstance[$alias] = $resolved;
        }
        //if container 
        if ($alias === "app") {
            return static::$resolvedInstance[$alias] = static::$container;
        }
        // 3) FQCN fallback (last resort; bypasses DI)
        if (\class_exists($alias)) {
            if (isStaticMethod($alias, 'getInstance')) {
                return static::$resolvedInstance[$alias] = $alias::getInstance();
            }

            /** @psalm-suppress MixedMethodCall */
            return static::$resolvedInstance[$alias] = new $alias();
        }
        throw new FacadeException("Facade alias '{$alias}' not found in container and class does not exist.");
    }

    /**
     * Magic static caller that forwards calls to the underlying instance.
     *
     * @param string $method
     * @param array<int,mixed> $params
     * @return mixed
     */
    public static function __callStatic(string $method, array $params): mixed
    {
        $instance = static::getInstance();

        if (!\method_exists($instance, $method)) {
            $alias = static::getClassAlias();
            $type  = \get_debug_type($instance);
            throw new FacadeException("Method '{$method}' does not exist on facade target [{$alias}] of type [{$type}].");
        }

        try {
            /** @psalm-suppress MixedMethodCall */
            return $instance->$method(...$params);
        } catch (\Throwable $e) {
            $alias = static::getClassAlias();
            throw new FacadeException("Error calling '{$method}' on facade target [{$alias}]: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetches the alias and validates it.
     */
    private static function getAliasOrFail(): string
    {
        $alias = static::getClassAlias();
        if ($alias === '') {
            throw new FacadeException('Facade alias not set (empty string).');
        }
        return $alias;
    }
}
