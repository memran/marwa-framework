<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Framework\Contracts\MiddlewarePipelineInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;

final class MiddlewareBootstrapper
{
    /**
     * @param list<MiddlewareInterface|class-string|array{0: class-string, 1: mixed, ...}> $middlewares
     */
    public function bootstrap(MiddlewarePipelineInterface $pipeline, array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $normalized = $this->normalize($middleware);
            $pipeline->push($normalized);
        }
    }

    /**
     * Normalize middleware to MiddlewareInterface instance.
     *
     * Supports:
     * - MiddlewareInterface (returned as-is)
     * - class-string (instantiated from container)
     * - [class_string, 'arg1', 'arg2', ...] (factory or constructor with args)
     *
     * @param MiddlewareInterface|class-string|array{0: class-string, 1: mixed, ...} $middleware
     */
    /**
     * @param MiddlewareInterface|class-string|array{0: class-string, 1: mixed, ...} $middleware
     */
    private function normalize(mixed $middleware): MiddlewareInterface
    {
        $type = get_debug_type($middleware);

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if ($type === 'string') {
            return $this->resolveFromContainer($middleware);
        }

        if ($type === 'array') {
            $first = reset($middleware);

            if ($first !== false && is_string($first)) {
                $args = array_slice($middleware, 1);

                return $this->resolveWithArgs($first, $args);
            }

            throw new RuntimeException(sprintf(
                'Invalid middleware descriptor: first element must be class-string. Got %s',
                get_debug_type($first ?: null)
            ));
        }

        throw new RuntimeException(sprintf(
            'Invalid middleware: expected MiddlewareInterface, class-string, or [class, args]. Got %s',
            $type
        ));
    }

    private function resolveFromContainer(string $class): MiddlewareInterface
    {
        static $container;

        if ($container === null) {
            global $app;
            $container = $app->container();
        }

        return $container->get($class);
    }

    /**
     * @param list<mixed> $args
     */
    private function resolveWithArgs(string $class, array $args): MiddlewareInterface
    {
        if (method_exists($class, 'create')) {
            return $class::create(...$args);
        }

        $reflector = new \ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $params = $constructor->getParameters();
        $resolvedArgs = [];

        foreach ($params as $i => $param) {
            if (isset($args[$i])) {
                $resolvedArgs[] = $this->resolveArg($args[$i], $param);
            } elseif ($param->isDefaultValueAvailable()) {
                $resolvedArgs[] = $param->getDefaultValue();
            } else {
                $resolvedArgs[] = null;
            }
        }

        return $reflector->newInstanceArgs($resolvedArgs);
    }

    /**
     * @param mixed $arg
     */
    private function resolveArg(mixed $arg, \ReflectionParameter $param): mixed
    {
        if (is_string($arg) && $param->getType() !== null) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                return $this->resolveFromContainer($arg);
            }
        }

        return $arg;
    }
}
