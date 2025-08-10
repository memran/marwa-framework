<?php

namespace Marwa\App\Security;

class XSSProtection
{
    /**
     * Escape HTML to prevent XSS attacks.
     *
     * @param string $input The user input to escape.
     * @return string The escaped HTML.
     */
    public static function escape(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Clean text by removing dangerous HTML tags.
     *
     * @param string $input The user input to clean.
     * @return string The cleaned text.
     */
    public static function strip(string $input): string
    {
        return strip_tags($input, '<b><i><u><a>');
    }
}
