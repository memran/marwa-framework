<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Marwa\Router\Response;

class MaintenanceMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (env('MAINTENANCE', 0)) {
            return Response::json([
                'error' => 'Service temporarily unavailable for maintenance',
                'estimated_recovery' => date('c', time() + env('MAINTENANCE_TIME', 300))
            ], 503);
        }

        return $handler->handle($request);
    }
}
