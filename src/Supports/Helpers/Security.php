<?php

declare(strict_types=1);

/**
 * Security Helper Functions
 */

if (!function_exists('security')) {
    function security(): \Marwa\Framework\Contracts\SecurityInterface
    {
        return app(\Marwa\Framework\Contracts\SecurityInterface::class);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return security()->csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return security()->csrfField();
    }
}

if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token(string $token): bool
    {
        return security()->validateCsrfToken($token);
    }
}

if (!function_exists('is_trusted_host')) {
    function is_trusted_host(string $host): bool
    {
        return security()->isTrustedHost($host);
    }
}

if (!function_exists('is_trusted_origin')) {
    function is_trusted_origin(string $origin): bool
    {
        return security()->isTrustedOrigin($origin);
    }
}

if (!function_exists('throttle')) {
    function throttle(string $key, ?int $limit = null, ?int $window = null): bool
    {
        return security()->throttle($key, $limit, $window);
    }
}

if (!function_exists('sanitize_filename')) {
    function sanitize_filename(string $name): string
    {
        return security()->sanitizeFilename($name);
    }
}

if (!function_exists('safe_path')) {
    function safe_path(string $path, string $basePath): string
    {
        return security()->safePath($path, $basePath);
    }
}
