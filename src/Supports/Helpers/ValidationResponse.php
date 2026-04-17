<?php

declare(strict_types=1);

/**
 * Validation and Response Helper Functions
 */

if (!function_exists('validate_request')) {
    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    function validate_request(
        array $rules,
        array $messages = [],
        array $attributes = [],
        ?\Psr\Http\Message\ServerRequestInterface $request = null
    ): array {
        /** @var \Marwa\Framework\Adapters\Validation\RequestValidatorAdapter $validator */
        $validator = app(\Marwa\Framework\Adapters\Validation\RequestValidatorAdapter::class);

        return $validator->validateRequest($request ?? request(), $rules, $messages, $attributes);
    }
}

if (!function_exists('old')) {
    /**
     * @param array<string, mixed>|null $default
     */
    function old(?string $key = null, mixed $default = null): mixed
    {
        $data = session('_old_input', []);

        if (!is_array($data)) {
            $data = [];
        }

        if ($key === null) {
            return $data;
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

if (!function_exists('response')) {
    function response(string $body = '', int $status = 200): \Psr\Http\Message\ResponseInterface
    {
        return \Marwa\Router\Response::html($body, $status);
    }
}
