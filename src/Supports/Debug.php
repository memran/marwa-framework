<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

final class Debug
{
    private static bool $enabled = false;
    private static int $previousErrorReporting = E_ALL;
    private static string|false $previousDisplayErrors = false;

    /**
     * Toggle PHP runtime error display for local development.
     */
    public static function enable(bool $jsonMode = false): void
    {
        if (self::$enabled) {
            return;
        }

        self::$previousErrorReporting = error_reporting();
        self::$previousDisplayErrors = ini_get('display_errors');
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        self::$enabled = true;
    }

    /**
     * Restore the previous PHP error display settings.
     */
    public static function disable(): void
    {
        error_reporting(self::$previousErrorReporting);
        ini_set('display_errors', self::$previousDisplayErrors === false ? '0' : (string) self::$previousDisplayErrors);
        self::$enabled = false;
    }

    /**
     * Check if debug mode is currently enabled
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }
}
