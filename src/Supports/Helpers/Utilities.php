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
