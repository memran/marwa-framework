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
        $requestId = $this->resolveRequestId($request->getHeaderLine('X-Request-ID'));
        $logger = logger();

        if (method_exists($logger, 'setRequestId')) {
            $logger->setRequestId($requestId);
        }

        $request = $request->withAttribute('request_id', $requestId);

        return $handler->handle($request)->withHeader('X-Request-ID', $requestId);
    }

    private function resolveRequestId(string $requestId): string
    {
        $requestId = trim($requestId);

        if ($requestId !== '' && preg_match('/\A[a-zA-Z0-9._-]{1,128}\z/', $requestId) === 1) {
            return $requestId;
        }

        return bin2hex(random_bytes(16));
    }
}
