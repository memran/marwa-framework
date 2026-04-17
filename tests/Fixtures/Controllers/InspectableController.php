<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Controllers;

use Marwa\Framework\Controllers\Controller;
use Marwa\Support\Validation\ErrorBag;
use Marwa\Framework\Adapters\Validation\FormRequestAdapter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class InspectableController extends Controller
{
    public function requestValue(?string $key = null, mixed $default = null): mixed
    {
        return $this->request($key, $default);
    }

    public function inputValue(?string $key = null, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    public function validateValue(
        array $rules,
        array $messages = [],
        array $attributes = [],
        ?ServerRequestInterface $request = null
    ): array {
        return $this->validate($rules, $messages, $attributes, $request);
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedValue(FormRequestAdapter $request): array
    {
        return $this->validated($request);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderView(string $template, array $data = [], int $status = 200): ResponseInterface
    {
        return $this->view($template, $data, $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function jsonValue(array $data, int $status = 200): ResponseInterface
    {
        return $this->json($data, $status);
    }

    public function redirectValue(string $uri, int $status = 302): ResponseInterface
    {
        return $this->redirect($uri, $status);
    }

    public function backValue(int $status = 302): ResponseInterface
    {
        return $this->back($status);
    }

    /**
     * @param array<string, mixed>|null $input
     */
    public function withInputValue(?array $input = null): static
    {
        return $this->withInput($input);
    }

    /**
     * @param array<string, list<string>|string>|ErrorBag $errors
     */
    public function withErrorsValue(array|ErrorBag $errors): static
    {
        return $this->withErrors($errors);
    }

    public function flashValue(string $key, mixed $value): static
    {
        return $this->flash($key, $value);
    }

    public function authorizeValue(bool $condition, string $message = 'Forbidden'): ?ResponseInterface
    {
        return $this->authorize($condition, $message);
    }

    public function abortUnlessValue(bool $condition, string $message = 'Forbidden', int $status = 403): ?ResponseInterface
    {
        return $this->abortUnless($condition, $message, $status);
    }

    public function abortIfValue(bool $condition, string $message = 'Forbidden', int $status = 403): ?ResponseInterface
    {
        return $this->abortIf($condition, $message, $status);
    }
}
