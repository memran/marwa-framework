<?php

declare(strict_types=1);

/**
 * Utility Helper Functions
 */

use Marwa\Support\Helper;
use Marwa\Support\Random;

if (!function_exists('generate_key')) {
    /**
     * Generate a cryptographically secure random key.
     *
     * @param int $length The length of the key (in bytes, default: 32)
     * @param bool $asHex Whether to return the key as a hex string
     * @return string The generated key
     * @throws \RuntimeException If secure random bytes could not be generated
     */
    function generate_key(int $length = 32, bool $asHex = true): string
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException('Key length must be greater than 0.');
        }

        $randomBytes = Random::bytes($length);

        return $asHex ? bin2hex($randomBytes) : $randomBytes;
    }
}

if (!function_exists('ensure_directory')) {
    /**
     * Ensure a directory exists, creating it if necessary.
     *
     * @param string $path The directory path to ensure
     * @param int $mode The directory permissions
     * @return bool True if directory exists or was created
     * @throws \RuntimeException If directory cannot be created
     */
    function ensure_directory(string $path, int $mode = 0777): bool
    {
        if (is_dir($path)) {
            return true;
        }

        if (!mkdir($path, $mode, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $path));
        }

        return true;
    }
}

if (!function_exists('json_safe_decode')) {
    /**
     * Decode a JSON string to an array with throw on error.
     *
     * @param string $json The JSON string to decode
     * @param bool $associative Whether to return an associative array
     * @param int $depth Maximum depth
     * @return mixed The decoded value
     * @throws \JsonException If the JSON cannot be decoded
     */
    function json_safe_decode(string $json, bool $associative = true, int $depth = 512): mixed
    {
        return \json_decode($json, $associative, $depth, JSON_THROW_ON_ERROR);
    }
}

if (!function_exists('with')) {
    /**
     * Return the given value, optionally passing it to a callback.
     *
     * @template T
     * @param T $value
     * @param callable $callback
     * @return T
     */
    function with($value, callable $callback)
    {
        $resolver = $callback instanceof \Closure ? $callback : \Closure::fromCallable($callback);

        return Helper::value($resolver, $value) ?? $value;
    }
}

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value and then return the value.
     *
     * This is useful for performing side effects without breaking method chains.
     *
     * @template T
     * @param T $value
     * @param callable $callback
     * @return T
     */
    function tap($value, callable $callback)
    {
        return Helper::tap($value, $callback);
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        Helper::dd(...$vars);
    }
}
