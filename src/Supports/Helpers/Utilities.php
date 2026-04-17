<?php

declare(strict_types=1);

/**
 * Utility Helper Functions
 */

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

        try {
            $randomBytes = random_bytes($length);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to generate a secure key.', 0, $e);
        }

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
        return $callback($value) ?? $value;
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
        $callback($value);

        return $value;
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            var_dump($var);
        }

        exit(1);
    }
}
