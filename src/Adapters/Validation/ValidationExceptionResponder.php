<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Validation;

use Marwa\Router\Response;
use Marwa\Support\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ValidationExceptionResponder
{
    public function toResponse(ValidationException $exception, ServerRequestInterface $request): ResponseInterface
    {
        $session = session();
        if ($session !== null) {
            $session->flash('errors', $exception->errors()->all());
            $session->flash('_old_input', $exception->input());
        }

        if ($this->expectsJson($request)) {
            return Response::json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors()->all(),
            ], 422);
        }

        $target = $request->getHeaderLine('Referer');
        if (trim($target) === '') {
            $target = (string) $request->getUri();
        }

        return Response::redirect($target !== '' ? $target : '/', 302);
    }

    private function expectsJson(ServerRequestInterface $request): bool
    {
        $accept = strtolower($request->getHeaderLine('Accept'));

        return str_contains($accept, 'application/json')
            || str_contains($accept, 'text/json')
            || str_contains($accept, '+json');
    }
}
