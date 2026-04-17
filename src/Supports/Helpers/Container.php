<?php

declare(strict_types=1);

/**
 * Container Helper Functions
 */

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        $app = $GLOBALS['marwa_app'] ?? null;

        if (!$app instanceof \Marwa\Framework\Application) {
            throw new \RuntimeException('Application instance not set. Assign $GLOBALS["marwa_app"] = $app at bootstrap.');
        }

        return $abstract ? $app->make($abstract) : $app;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return app()->make(\Marwa\Framework\Supports\Config::class)->get($key, $default);
    }
}

if (!function_exists('cache')) {
    function cache(?string $key = null, mixed $default = null): mixed
    {
        /** @var \Marwa\Framework\Contracts\CacheInterface $cache */
        $cache = app(\Marwa\Framework\Contracts\CacheInterface::class);

        if ($key !== null) {
            return $cache->get($key, $default);
        }

        return $cache;
    }
}

if (!function_exists('storage')) {
    function storage(?string $disk = null): \Marwa\Framework\Supports\Storage
    {
        /** @var \Marwa\Framework\Supports\Storage $storage */
        $storage = app(\Marwa\Framework\Supports\Storage::class);

        return $disk !== null ? $storage->disk($disk) : $storage;
    }
}

if (!function_exists('db')) {
    function db(): \Marwa\DB\Connection\ConnectionManager
    {
        /** @var \Marwa\DB\Connection\ConnectionManager $manager */
        $manager = app(\Marwa\DB\Connection\ConnectionManager::class);

        return $manager;
    }
}
