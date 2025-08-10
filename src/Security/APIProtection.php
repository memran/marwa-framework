<?php

namespace Marwa\App\Security;

class APIProtection
{
    /**
     * Validate the provided API key.
     *
     * @param string $apiKey The API key to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function validateKey(string $apiKey): bool
    {
        $validApiKeys = ['your-api-key'];
        return in_array($apiKey, $validApiKeys);
    }

    /**
     * Rate limit requests based on IP address.
     *
     * @param string $ip The user's IP address.
     * @return bool True if allowed, false if rate-limited.
     */
    public static function rateLimit(string $ip): bool
    {
        // Implement logic to track and limit requests based on IP address.
        return true;
    }
}
