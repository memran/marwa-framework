<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports\Helpers;

if (!function_exists('module_config')) {
    /**
     * Get module-specific config value.
     *
     * Usage:
     *   module_config('users.theme')           // from manifest or config file
     *   module_config('users.settings.page') // nested config
     */
    function module_config(string $key, mixed $default = null): mixed
    {
        $config = app()->config();

        $value = $config->get($key, $default);

        return $value ?? $default;
    }
}