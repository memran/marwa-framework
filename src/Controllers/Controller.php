<?php

declare(strict_types=1);

namespace Marwa\Framework\Controllers;

use JsonSerializable;
use Marwa\Entity\Validation\ErrorBag;
use Marwa\Framework\Validation\FormRequest;
use Marwa\Framework\Validation\ValidationException;
use Marwa\Router\Http\Input;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Controller
{
    protected function request(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            /** @var ServerRequestInterface $request */
            $request = app(ServerRequestInterface::class);

            return $request;
        }

        return Input::get($key, $default);
    }

    protected function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Input::all();
        }

        return Input::get($key, $default);
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    protected function validate(
        array $rules,
        array $messages = [],
        array $attributes = [],
        ?ServerRequestInterface $request = null
    ): array {
        return validate_request($rules, $messages, $attributes, $request);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(FormRequest $request): array
    {
        return $request->validated();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    protected function view(string $template, array $data = [], int $status = 200, array $headers = []): ResponseInterface
    {
        return Response::html($this->render($template, $data), $status, $headers);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data = []): string
    {
        /** @var \Marwa\Framework\Views\View $view */
        $view = app(\Marwa\Framework\Views\View::class);

        return $view->render($template, $data);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $data
     */
    protected function json(array|JsonSerializable $data, int $status = 200, array $headers = []): ResponseInterface
    {
        return Response::json($data, $status, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function response(string $body = '', int $status = 200, array $headers = []): ResponseInterface
    {
        return Response::html($body, $status, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function redirect(string $uri, int $status = 302, array $headers = []): ResponseInterface
    {
        return Response::redirect($uri, $status, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function back(int $status = 302, array $headers = []): ResponseInterface
    {
        /** @var ServerRequestInterface $request */
        $request = app(ServerRequestInterface::class);
        $target = trim($request->getHeaderLine('Referer'));

        if ($target === '') {
            $target = (string) $request->getUri();
        }

        if ($target === '') {
            $target = '/';
        }

        return Response::redirect($target, $status, $headers);
    }

    protected function flash(string $key, mixed $value): static
    {
        session()->flash($key, $value);

        return $this;
    }

    /**
     * @param array<string, mixed>|null $input
     */
    protected function withInput(?array $input = null): static
    {
        session()->flash(ValidationException::OLD_INPUT_KEY, $input ?? Input::all());

        return $this;
    }

    /**
     * @param array<string, list<string>|string>|ErrorBag $errors
     */
    protected function withErrors(array|ErrorBag $errors): static
    {
        $payload = $errors instanceof ErrorBag ? $errors->all() : $this->normalizeErrors($errors);
        session()->flash(ValidationException::ERROR_BAG_KEY, $payload);

        return $this;
    }

    protected function old(?string $key = null, mixed $default = null): mixed
    {
        return old($key, $default);
    }

    protected function session(?string $key = null, mixed $default = null): mixed
    {
        return session($key, $default);
    }

    protected function unauthorized(string $message = 'Unauthorized'): ResponseInterface
    {
        return Response::unauthorized($message);
    }

    protected function forbidden(string $message = 'Forbidden'): ResponseInterface
    {
        return Response::forbidden($message);
    }

    protected function abortIf(bool $condition, string $message = 'Forbidden', int $status = 403): ?ResponseInterface
    {
        if (!$condition) {
            return null;
        }

        return $this->abortResponse($message, $status);
    }

    protected function abortUnless(bool $condition, string $message = 'Forbidden', int $status = 403): ?ResponseInterface
    {
        if ($condition) {
            return null;
        }

        return $this->abortResponse($message, $status);
    }

    protected function authorize(bool $condition, string $message = 'Forbidden'): ?ResponseInterface
    {
        return $this->abortUnless($condition, $message, 403);
    }

    /**
     * @param array<string, list<string>|string> $errors
     * @return array<string, list<string>>
     */
    private function normalizeErrors(array $errors): array
    {
        $normalized = [];

        foreach ($errors as $field => $messages) {
            if (is_string($messages)) {
                $normalized[$field] = [$messages];
                continue;
            }

            $normalized[$field] = array_values(array_filter(
                array_map(static fn (mixed $message): string => (string) $message, $messages),
                static fn (string $message): bool => $message !== ''
            ));
        }

        return $normalized;
    }

    protected function abortResponse(string $message, int $status): ResponseInterface
    {
        return match ($status) {
            401 => $this->unauthorized($message),
            403 => $this->forbidden($message),
            default => Response::html($message, $status),
        };
    }
}
