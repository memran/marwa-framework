<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use Marwa\Framework\Authorization\Contracts\GateInterface;
use Marwa\Framework\Exceptions\AuthorizationException;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private GateInterface $gate
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ability = $this->parseAbility($request);

        if ($ability === null) {
            return $handler->handle($request);
        }

        $resource = $this->parseResource($request);

        try {
            $this->gate->authorize($ability, $resource);
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }

        return $handler->handle($request);
    }

    private function parseAbility(ServerRequestInterface $request): ?string
    {
        $middleware = $request->getAttribute('middleware');

        if (!is_string($middleware)) {
            return null;
        }

        if (!str_starts_with($middleware, 'can:')) {
            return null;
        }

        $ability = substr($middleware, 4);

        return $ability !== '' ? trim($ability) : null;
    }

    private function parseResource(ServerRequestInterface $request): mixed
    {
        $middleware = $request->getAttribute('middleware');

        if (!is_string($middleware) || !str_contains($middleware, '@')) {
            return null;
        }

        $segment = substr($middleware, strpos($middleware, '@') + 1);

        if ($segment === '') {
            return null;
        }

        $attributeName = trim($segment);

return $request->getAttribute($attributeName);
    }
}

