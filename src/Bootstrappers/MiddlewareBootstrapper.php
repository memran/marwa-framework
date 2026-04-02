<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Framework\Contracts\MiddlewarePipelineInterface;
use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareBootstrapper
{
    /**
     * @param list<MiddlewareInterface> $middlewares
     */
    public function bootstrap(MiddlewarePipelineInterface $pipeline, array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $pipeline->push($middleware);
        }
    }
}
