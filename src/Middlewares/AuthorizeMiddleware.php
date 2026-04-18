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

class AuthorizeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private GateInterface $gate
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ability = $request->getAttribute('ability');

        if ($ability !== null) {
            $resource = $request->getAttribute('resource');

            try {
                $this->gate->authorize($ability, $resource);
            } catch (AuthorizationException $e) {
                return Response::forbidden($e->getMessage());
            }
        }

        return $handler->handle($request);
    }
}
