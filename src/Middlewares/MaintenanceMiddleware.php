<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

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
