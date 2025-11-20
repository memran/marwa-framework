<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Http;

use League\Container\Container;
use Marwa\Framework\Contracts\MiddlewarePipelineInterface;
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\MiddlewareInterface;
use Relay\Relay;

/**
 * Application-level PSR-15 pipeline powered by Relay.
 * Append global middlewares in order, then the RouterDispatchMiddleware last.
 */
final class RelayPipelineAdapter implements MiddlewarePipelineInterface
{

    /** @var MiddlewareInterface[] */
    private array $stack = [];

    public function __construct(public Container $container) {}
    public function push(MiddlewareInterface|string $middleware): self
    {
        //$this->stack[] = $middleware;
        // 1. If it’s already an object and valid, keep it
        if (is_object($middleware) && $middleware instanceof MiddlewareInterface) {
            $this->stack[] = $middleware;
        }
        // 2. If it’s a string, try to resolve
        else if (is_string($middleware)) {
            $middlewareClass = null;

            if ($this->container && $this->container->has($middleware)) {
                $middlewareClass = $this->container->get($middleware);
            } elseif (class_exists($middleware)) {
                $middlewareClass = new $middleware();
                echo 'detected';
                die;
            }

            if ($middlewareClass instanceof MiddlewareInterface || is_callable($middlewareClass)) {
                $this->stack[] = $middlewareClass;
            } else {
                throw new \RuntimeException(
                    "Middleware '$middleware' could not be resolved to a callable or MiddlewareInterface"
                );
            }
        } else {
            // 3. Anything else is invalid
            throw new \RuntimeException('Invalid middleware entry in config.');
        }

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->stack)) {
            throw new \RuntimeException('Pipeline is empty. Add middlewares before handling requests.');
        }
        return (new Relay($this->stack))->handle($request);
    }
}
