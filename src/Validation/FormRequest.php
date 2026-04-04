<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation;

use Marwa\Router\Http\FormRequest as RouterFormRequest;
use Psr\Http\Message\ServerRequestInterface;

abstract class FormRequest extends RouterFormRequest
{
    /** @var array<string, mixed>|null */
    private ?array $validatedCache = null;

    private ?ValidationException $validationException = null;

    public function __construct(
        ServerRequestInterface $request,
        ?RequestValidator $validator = null,
    ) {
        parent::__construct($request, $validator ?? $this->resolveValidator());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function failedAuthorization(): never
    {
        throw new \RuntimeException('This action is unauthorized.', 403);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    protected function prepareForValidation(array $input): array
    {
        return $input;
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    protected function passedValidation(array $validated): array
    {
        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        if ($this->validatedCache !== null) {
            return $this->validatedCache;
        }

        if (!$this->authorize()) {
            $this->failedAuthorization();
        }

        $input = $this->prepareForValidation($this->all());

        try {
            $validated = $this->validator()->validateInput(
                $input,
                $this->rules(),
                $this->messages(),
                $this->attributes()
            );

            $this->validationException = null;
            $this->validatedCache = $this->passedValidation($validated);

            return $this->validatedCache;
        } catch (ValidationException $exception) {
            $this->validationException = $exception;
            throw $exception;
        }
    }

    /**
     * Alias for validated() to keep Laravel-style ergonomics.
     *
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        return $this->validated();
    }

    /**
     * @return array<string, mixed>
     */
    public function safe(): array
    {
        return $this->validated();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $data = [];

        $query = $this->request()->getQueryParams();
        $data = array_replace_recursive($data, $query);

        $body = $this->request()->getParsedBody();
        if (is_array($body)) {
            $data = array_replace_recursive($data, $body);
        }

        $params = $this->request()->getAttribute('params');
        if (is_array($params)) {
            $data = array_replace_recursive($data, $params);
        }

        $files = $this->request()->getUploadedFiles();
        if ($files !== []) {
            $data = array_replace_recursive($data, $files);
        }

        return $data;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->valueFor($this->all(), $key, $default);
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $data = $this->all();
        $selected = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $selected[$key] = $data[$key];
            }
        }

        return $selected;
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        $skip = array_flip($keys);
        $filtered = [];

        foreach ($this->all() as $key => $value) {
            if (!array_key_exists($key, $skip)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    public function validationException(): ?ValidationException
    {
        return $this->validationException;
    }

    protected function failedValidation(ValidationException $exception): never
    {
        throw $exception;
    }

    private function resolveValidator(): RequestValidator
    {
        try {
            return app(RequestValidator::class);
        } catch (\Throwable) {
            return new RequestValidator();
        }
    }

    private function validator(): RequestValidator
    {
        return $this->resolveValidator();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function valueFor(array $data, string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $default;
        }

        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        $current = $data;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
