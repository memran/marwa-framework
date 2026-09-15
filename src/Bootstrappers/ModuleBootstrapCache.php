<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Module\Module;

/**
 * Compiled module bootstrap cache.
 *
 * Stores the discovered module rows (slug, base path, manifest) plus derived
 * bootstrap metadata in a single PHP file so requests can skip the module
 * filesystem scan. Freshness is decided by a fingerprint of every module root
 * listing and manifest (mtime + size), mirroring how Twig decides whether a
 * compiled template is still current. In production the file is trusted until
 * `cache:clear`, `bootstrap:clear`, or `module:clear` removes it.
 */
final class ModuleBootstrapCache
{
    public const SCHEMA = 1;

    /**
     * @param list<string> $modulePaths
     */
    public function __construct(
        private string $cacheFile,
        private array $modulePaths,
        private bool $forceRefresh = false,
        private bool $trustCache = false
    ) {}

    public function enabled(): bool
    {
        return $this->cacheFile !== '';
    }

    public function path(): string
    {
        return $this->cacheFile;
    }

    /**
     * Load the compiled payload when it exists, matches the current schema,
     * and is still fresh (unless the cache is trusted).
     *
     * @return array{
     *     schema:int,
     *     version:string,
     *     generated_at:int,
     *     modules:array<string, mixed>,
     *     providers:list<string>,
     *     view_namespaces:array<string, list<string>>
     * }|null
     */
    public function load(): ?array
    {
        if (!$this->enabled() || $this->forceRefresh || !is_file($this->cacheFile)) {
            return null;
        }

        try {
            /** @var mixed $payload */
            $payload = include $this->cacheFile;
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($payload) || ($payload['schema'] ?? null) !== self::SCHEMA) {
            return null;
        }

        $rows = $payload['modules'] ?? null;
        $version = $payload['version'] ?? null;
        $providers = $payload['providers'] ?? null;

        if (!is_array($rows) || $rows === [] || !is_string($version) || !is_array($providers)) {
            return null;
        }

        if (!$this->trustCache && !hash_equals($version, $this->fingerprint($rows))) {
            return null;
        }

        return [
            'schema' => self::SCHEMA,
            'version' => $version,
            'generated_at' => is_int($payload['generated_at'] ?? null) ? $payload['generated_at'] : 0,
            'modules' => $rows,
            'providers' => self::stringList($providers),
            'view_namespaces' => self::namespaceMap($payload['view_namespaces'] ?? []),
        ];
    }

    /**
     * Build the compiled payload for the given modules.
     *
     * @param array<string, Module> $modules
     * @return array{
     *     schema:int,
     *     version:string,
     *     generated_at:int,
     *     modules:array<string, array{slug:string, basePath:string, manifest:array<string, mixed>}>,
     *     providers:list<string>,
     *     view_namespaces:array<string, list<string>>
     * }
     */
    public function compile(array $modules): array
    {
        $rows = [];

        foreach ($modules as $slug => $module) {
            $rows[$slug] = [
                'slug' => $module->slug(),
                'basePath' => $module->basePath(),
                'manifest' => $module->manifest(),
            ];
        }

        return [
            'schema' => self::SCHEMA,
            'version' => $this->fingerprint($rows),
            'generated_at' => time(),
            'modules' => $rows,
            'providers' => self::providers($modules),
            'view_namespaces' => self::viewNamespaces($modules),
        ];
    }

    /**
     * Persist the payload. Failures are silent so bootstrap can continue with
     * a cold scan when the cache directory is not writable.
     *
     * @param array<string, mixed> $payload
     */
    public function save(array $payload): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        $directory = dirname($this->cacheFile);

        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false;
        }

        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($payload, true) . ";\n";
        $temporary = $this->cacheFile . '.' . bin2hex(random_bytes(6)) . '.tmp';

        if (@file_put_contents($temporary, $contents, LOCK_EX) === false) {
            return false;
        }

        if (!@rename($temporary, $this->cacheFile)) {
            @unlink($temporary);

            return false;
        }

        return true;
    }

    public function clear(): bool
    {
        if (!$this->enabled() || !is_file($this->cacheFile)) {
            return false;
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($this->cacheFile, true);
        }

        return @unlink($this->cacheFile);
    }

    /**
     * Fingerprint every module root listing and manifest so added, removed,
     * or edited modules invalidate the compiled payload.
     *
     * @param array<mixed> $rows
     */
    public function fingerprint(array $rows): string
    {
        $parts = [];

        foreach ($this->modulePaths as $root) {
            $parts[] = 'root:' . $root;
            $parts[] = implode(',', $this->rootManifests($root));
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $basePath = $row['basePath'] ?? null;

            if (!is_string($basePath) || $basePath === '') {
                continue;
            }

            $parts[] = $basePath
                . '|' . self::fileStamp($basePath . DIRECTORY_SEPARATOR . 'manifest.php')
                . '|' . self::fileStamp($basePath . DIRECTORY_SEPARATOR . 'manifest.json');
        }

        return hash('sha256', implode("\n", $parts));
    }

    /**
     * @param array<string, Module> $modules
     * @return list<string>
     */
    public static function providers(array $modules): array
    {
        $providers = [];

        foreach ($modules as $module) {
            foreach ($module->providers() as $provider) {
                $providers[$provider] = true;
            }
        }

        return array_keys($providers);
    }

    /**
     * @param array<string, Module> $modules
     * @return array<string, list<string>>
     */
    public static function viewNamespaces(array $modules): array
    {
        $namespaces = [];

        foreach ($modules as $module) {
            $paths = self::viewPaths($module);

            if ($paths === []) {
                continue;
            }

            $namespaces[self::namespace($module->slug())] = $paths;
        }

        return $namespaces;
    }

    /**
     * @return list<string>
     */
    private static function viewPaths(Module $module): array
    {
        $paths = [];
        $basePath = $module->basePath();

        $conventionalPath = $basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';

        if (is_dir($conventionalPath)) {
            $paths[] = $conventionalPath;
        }

        $declaredPath = $module->path('views');

        if ($declaredPath !== null && is_dir($declaredPath)) {
            $paths[] = $declaredPath;
        }

        return array_values(array_unique($paths));
    }

    private static function namespace(string $slug): string
    {
        $namespace = preg_replace('/[^A-Za-z0-9_]/', '_', $slug) ?: 'Module';

        if (preg_match('/^[A-Za-z]/', $namespace) !== 1) {
            $namespace = 'Module_' . $namespace;
        }

        return $namespace;
    }

    /**
     * @return list<string>
     */
    private function rootManifests(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $entries = [];

        foreach (new \DirectoryIterator($root) as $file) {
            if ($file->isDot() || !$file->isDir()) {
                continue;
            }

            $moduleDir = $file->getPathname();

            if (
                is_file($moduleDir . DIRECTORY_SEPARATOR . 'manifest.php')
                || is_file($moduleDir . DIRECTORY_SEPARATOR . 'manifest.json')
            ) {
                $entries[] = $file->getBasename();
            }
        }

        sort($entries);

        return $entries;
    }

    private static function fileStamp(string $file): string
    {
        $mtime = @filemtime($file);
        $size = @filesize($file);

        return ($mtime === false ? '-' : (string) $mtime) . ':' . ($size === false ? '-' : (string) $size);
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $strings = [];

        foreach ($value as $entry) {
            if (is_string($entry) && $entry !== '') {
                $strings[] = $entry;
            }
        }

        return array_values(array_unique($strings));
    }

    /**
     * @return array<string, list<string>>
     */
    private static function namespaceMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $map = [];

        foreach ($value as $namespace => $paths) {
            if (!is_string($namespace) || !is_array($paths)) {
                continue;
            }

            $map[$namespace] = self::stringList($paths);
        }

        return $map;
    }
}
