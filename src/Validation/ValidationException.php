<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation;

use Marwa\Entity\Validation\ErrorBag;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ValidationException extends \InvalidArgumentException
{
    public const ERROR_BAG_KEY = 'errors';
    public const OLD_INPUT_KEY = '_old_input';

    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly ErrorBag $errors,
        private readonly array $input = [],
        string $message = 'The given data was invalid.',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function errors(): ErrorBag
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function input(): array
    {
        return $this->input;
    }

    public function toResponse(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return Response::json([
                'message' => $this->getMessage(),
                'errors' => $this->errors->all(),
            ], 422);
        }

        $session = session();
        if ($session !== null) {
            $session->flash(self::ERROR_BAG_KEY, $this->errors->all());
            $session->flash(self::OLD_INPUT_KEY, $this->input);
        }

        $target = $request->getHeaderLine('Referer');
        if (trim($target) === '') {
            $target = (string) $request->getUri();
        }

        if (trim($target) === '') {
            $target = '/';
        }

        return Response::redirect($target, 302);
    }

    private function expectsJson(ServerRequestInterface $request): bool
    {
        $accept = strtolower($request->getHeaderLine('Accept'));

        return str_contains($accept, 'application/json')
            || str_contains($accept, 'text/json')
            || str_contains($accept, '+json');
    }
}
