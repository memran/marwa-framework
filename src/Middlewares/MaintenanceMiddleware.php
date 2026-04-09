<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use Marwa\Framework\Facades\Config;
use Marwa\Framework\Facades\View;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class MaintenanceMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (env('MAINTENANCE', 0)) {
            $template = Config::get('app.maintenance.template');
            $message = Config::get('app.maintenance.message', 'Service temporarily unavailable for maintenance');
            $estimatedRecovery = date('c', time() + env('MAINTENANCE_TIME', 300));

            if ($template !== null && View::exists($template)) {
                return View::make($template, [
                    'message' => $message,
                    'estimated_recovery' => $estimatedRecovery,
                ])->withStatus(503);
            }

            return Response::html(
                <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Maintenance</title>
                    <style>
                        body {
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            min-height: 100vh;
                            margin: 0;
                            background: #f5f5f5;
                            color: #333;
                        }
                        .container {
                            text-align: center;
                            padding: 2rem;
                        }
                        h1 {
                            font-size: 2rem;
                            margin-bottom: 1rem;
                            color: #e74c3c;
                        }
                        p {
                            font-size: 1.1rem;
                            color: #666;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>Under Maintenance</h1>
                        <p>{$message}</p>
                        <p>Estimated recovery: {$estimatedRecovery}</p>
                    </div>
                </body>
                </html>
                HTML,
                503
            );
        }

        return $handler->handle($request);
    }
}
