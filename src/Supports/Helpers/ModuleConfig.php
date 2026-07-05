<?php

declare(strict_types=1);

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
        $config = app(\Marwa\Framework\Supports\Config::class);
        $moduleKey = 'modules.' . ltrim($key, '.');

        if ($config->has($moduleKey)) {
            return $config->get($moduleKey, $default) ?? $default;
        }

        $value = $config->get($key, $default);

        return $value ?? $default;
    }
}
