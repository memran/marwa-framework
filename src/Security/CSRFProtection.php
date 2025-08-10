<?php

namespace Marwa\App\Security;

class CSRFProtection
{
    /**
     * Generate a CSRF token for form validation.
     *
     * @return string The CSRF token.
     */
    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verify the CSRF token sent in the form.
     *
     * @param string $token The token to verify.
     * @throws \Exception If the CSRF token is invalid.
     */
    public static function verify(string $token): void
    {
        if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
            throw new \Exception('Invalid CSRF token');
        }
    }
}
