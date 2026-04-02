<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Http;

use League\Container\Container;
use Marwa\Framework\Contracts\MiddlewarePipelineInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
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

    public function push(MiddlewareInterface $middleware): self
    {
        $this->stack[] = $middleware;

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
