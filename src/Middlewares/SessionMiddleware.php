<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use Marwa\Framework\Contracts\SessionInterface;
use Marwa\Framework\Supports\EncryptedSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->session instanceof EncryptedSession && $this->session->shouldAutoStart()) {
            $this->session->start();
        }

        try {
            return $handler->handle($request);
        } finally {
            $this->session->close();
        }
    }
}
