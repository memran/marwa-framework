<?php

namespace Marwa\App\Security;

class InputSanitizer
{
    /**
     * Sanitize user input to remove unwanted characters.
     *
     * @param string $input The user input to sanitize.
     * @return string The sanitized input.
     */
    public static function clean(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');;
    }

    /**
     * Validate the input based on the specified type (email, URL).
     *
     * @param string $input The user input to validate.
     * @param string $type The type to validate (email, url).
     * @return bool True if valid, false otherwise.
     */
    public static function validate(string $input, string $type): bool
    {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) !== false;
            default:
                return false;
        }
    }
}
