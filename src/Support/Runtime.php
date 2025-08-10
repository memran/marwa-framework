<?php

declare(strict_types=1);

namespace Marwa\App\Support;

final class Runtime
{
    /**
     * Detect if running in console (CLI) mode.
     */
    public static function isConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * Detect if running in web (HTTP) mode.
     */
    public static function isWeb(): bool
    {
        return !self::isConsole();
    }
}
