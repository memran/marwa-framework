<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Module\Module;

final class ModulePathResolver
{
    /**
     * @param array<string, mixed> $moduleConfig
     * @return list<string>
     */
    public function resolveCommandPaths(Module $module, array $moduleConfig): array
    {
        $paths = [];

        foreach ($moduleConfig['commandPaths'] as $key) {
            $path = $module->path($key);

            if (is_string($path) && is_dir($path)) {
                $paths[] = $path;
            }
        }

        foreach ($moduleConfig['commandConventions'] as $relativePath) {
            $path = $module->basePath() . DIRECTORY_SEPARATOR . trim($relativePath, DIRECTORY_SEPARATOR);

            if (is_dir($path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param array<string, mixed> $moduleConfig
     * @return list<string>
     */
    public function resolveMigrationPaths(Module $module, array $moduleConfig): array
    {
        $paths = [];
        $basePath = $module->basePath();

        $conventionalPath = $basePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        if (is_dir($conventionalPath)) {
            $paths[] = $conventionalPath;
        }

        foreach ($module->migrations() as $migrationPath) {
            $normalizedPath = $this->normalizePath($migrationPath);

            if ($normalizedPath !== null) {
                $paths[] = $normalizedPath;
            }
        }

        if (empty($paths)) {
            foreach ($moduleConfig['migrationsPath'] as $key) {
                $path = $this->normalizePath($module->path($key));

                if ($path !== null) {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param array<string, mixed> $moduleConfig
     * @return list<string>
     */
    public function resolveSeederPaths(Module $module, array $moduleConfig): array
    {
        $paths = [];

        foreach ($moduleConfig['seedersPath'] as $key) {
            $path = $module->path($key);

            if (is_string($path) && is_dir($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function normalizePath(?string $path): ?string
    {
        if (!is_string($path) || $path === '') {
            return null;
        }

        if (is_file($path)) {
            $path = dirname($path);
        }

        if (!is_dir($path)) {
            return null;
        }

        $resolvedPath = realpath($path);

        return is_string($resolvedPath) ? $resolvedPath : null;
    }
}
