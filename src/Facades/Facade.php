<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

abstract class Facade
{
    /**
     * Child classes must return a container key/class to resolve.
     */
    abstract protected static function getFacadeAccessor(): string;

    public static function __callStatic(string $method, array $args)
    {
        $instance = app(static::getFacadeAccessor());
        return $instance->{$method}(...$args);
    }
}
