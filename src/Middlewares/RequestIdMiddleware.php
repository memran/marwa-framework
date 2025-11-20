<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = $request->getHeaderLine('X-Request-ID') ?: uniqid();
        logger()->setRequestId($requestId);
        $request = $request->withAttribute('request_id', $requestId);

        return $handler->handle($request);
    }
}
