<?php

declare(strict_types=1);

/**
 * Session, Request, and Env Helper Functions
 */

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        static $loadedPaths = [];

        $envPath = isset($GLOBALS['marwa_app']) && $GLOBALS['marwa_app'] instanceof \Marwa\Framework\Application
            ? base_path('.env')
            : getcwd() . DIRECTORY_SEPARATOR . '.env';

        if (!isset($loadedPaths[$envPath]) && is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
                $name = trim($name);
                $value = trim(preg_replace('/\s+#.*$/', '', $value) ?? $value);
                $value = trim($value, " \t\n\r\0\x0B\"'");

                if ($name === '') {
                    continue;
                }

                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }

            $loadedPaths[$envPath] = true;
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => is_numeric($value) ? (str_contains($value, '.') ? (float) $value : (int) $value) : $value,
        };
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        /** @var \Marwa\Framework\Contracts\SessionInterface $session */
        $session = app(\Marwa\Framework\Contracts\SessionInterface::class);

        if ($key !== null) {
            return $session->get($key, $default);
        }

        return $session;
    }
}

if (!function_exists('request')) {
    function request(?string $key = null, mixed $default = null): mixed
    {
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = app(\Psr\Http\Message\ServerRequestInterface::class);

        if ($key === null) {
            return $request;
        }

        return \Marwa\Router\Http\Input::get($key, $default);
    }
}
