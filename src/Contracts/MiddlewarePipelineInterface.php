<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\MiddlewareInterface;

interface MiddlewarePipelineInterface
{
    public function push(MiddlewareInterface $middleware): self;
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
