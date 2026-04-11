<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

final class Runtime
{
    private static ?bool $overrideConsole = null;

    public static function isConsole(): bool
    {
        if (self::$overrideConsole !== null) {
            return self::$overrideConsole;
        }

        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * Override the console detection result (useful for testing).
     * Pass null to clear the override.
     */
    public static function setConsoleOverride(?bool $isConsole): void
    {
        self::$overrideConsole = $isConsole;
    }

    /**
     * Detect if running in web (HTTP) mode.
     */
    public static function isWeb(): bool
    {
        return !self::isConsole();
    }

    public static function wantsJson(): bool
    {
        // Common signals for JSON responses (API/Ajax)
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $ct     = $_SERVER['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($ct, 'application/json')
            || strcasecmp($xhr, 'XMLHttpRequest') === 0;
    }
}
