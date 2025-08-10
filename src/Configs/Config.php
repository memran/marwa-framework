<?php

declare(strict_types=1);

namespace Marwa\App\Configs;

use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class Config
{
    /** @var array<string,mixed> */
    private array $items = [];
    private bool $loaded = false;

    private string $configPath;
    private string $cacheFile;
    private string $cacheMode = 'file'; // file|memory|none
    private bool $autoCache = false;

    public function __construct(
        ?string $configPath = null,
        ?string $cacheFile = null,
        string $cacheMode = 'file',
        bool $autoCache = false
    ) {
        $projectRoot      = $configPath;
        $this->configPath = rtrim($projectRoot . '/config', '/\\');
        $this->cacheFile  = $cacheFile ?? ($projectRoot . '/storage/cache/config.cache.php');
        $this->setCacheMode($cacheMode);
        $this->autoCache  = $autoCache;
    }

    // ---------------- Public API ----------------

    public function load(): void
    {
        $this->ensureLoaded();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureLoaded();
        return $this->dataGet($this->items, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureLoaded();
        $this->dataSet($this->items, $key, $value);
    }

    public function has(string $key): bool
    {
        return $this->get($key, "__missing__") !== "__missing__";
    }

    public function all(): array
    {
        $this->ensureLoaded();
        return $this->items;
    }

    public function setConfigPath(string $path): void
    {
        $this->configPath = rtrim($path, '/\\');
    }

    public function setCacheFile(string $path): void
    {
        $this->cacheFile = $path;
    }

    public function setCacheMode(string $mode): void
    {
        $valid = ['file', 'memory', 'none'];
        if (!in_array($mode, $valid, true)) {
            throw new RuntimeException("Invalid cache mode: " . implode(', ', $valid));
        }
        $this->cacheMode = $mode;
    }

    public function setAutoCache(bool $enabled): void
    {
        $this->autoCache = $enabled;
    }

    public function cache(): void
    {
        if (!$this->loaded) {
            throw new RuntimeException("Cannot cache before config is loaded.");
        }
        if ($this->cacheMode !== 'file') {
            return;
        }

        [$files, $hash] = $this->computeConfigFilesHash();

        $payload = [
            '__meta' => [
                'hash'  => $hash,
                'files' => $files,
                'time'  => time(),
            ],
            'items' => $this->items,
        ];

        $dir = \dirname($this->cacheFile);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create cache directory: {$dir}");
        }

        $export = var_export($payload, true);
        if (@file_put_contents($this->cacheFile, "<?php\nreturn {$export};") === false) {
            throw new RuntimeException("Failed to write cache file: {$this->cacheFile}");
        }
    }

    public function clearCache(): void
    {
        if (is_file($this->cacheFile)) {
            @unlink($this->cacheFile);
        }
    }

    public function reload(bool $respectCache = true): void
    {
        if (!$respectCache) {
            $this->clearCache();
        }
        $this->loaded = false;
        $this->items  = [];
        $this->ensureLoaded();
    }

    // ---------------- Internals ----------------

    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        if ($this->cacheMode === 'file' && is_file($this->cacheFile)) {
            $cached = require $this->cacheFile;
            if ($this->isCacheFresh($cached)) {
                $this->items  = $cached['items'];
                $this->loaded = true;
                return;
            }
        }

        $this->items = $this->loadConfigDirectory($this->configPath);
        $this->loaded = true;

        if ($this->autoCache && $this->cacheMode === 'file') {
            $this->cache();
        }
    }

    private function isCacheFresh(mixed $cached): bool
    {
        if (!is_array($cached) || !isset($cached['__meta']['hash'], $cached['items'])) {
            return false;
        }

        [$files, $hashNow] = $this->computeConfigFilesHash();
        return hash_equals($cached['__meta']['hash'], $hashNow);
    }

    private function loadConfigDirectory(string $dir): array
    {
        if (!is_dir($dir)) {
            throw new RuntimeException("Config directory not found: {$dir}");
        }

        $files = glob(rtrim($dir, '/\\') . '/*.{php,json,yml,yaml}', GLOB_BRACE) ?: [];
        $result = [];

        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $key = basename($file, '.' . $ext);
            $data = $this->parseConfigFile($file, $ext);
            $this->assertConfigArray($data, $file);
            $result[$key] = $data;
        }
        return $result;
    }

    private function parseConfigFile(string $file, string $ext): array
    {
        try {
            return match ($ext) {
                'php'   => require $file,
                'json'  => $this->parseJson($file),
                'yml', 'yaml' => $this->parseYaml($file),
                default => throw new RuntimeException("Unsupported config file: {$file}"),
            };
        } catch (\Throwable $e) {
            throw new RuntimeException("Failed to parse {$file}: " . $e->getMessage(), 0, $e);
        }
    }

    private function parseJson(string $file): array
    {
        $raw = file_get_contents($file);
        $data = json_decode((string)$raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON error: " . json_last_error_msg());
        }
        return $data;
    }

    private function parseYaml(string $file): array
    {
        try {
            return Yaml::parseFile($file);
        } catch (ParseException $e) {
            throw new RuntimeException("YAML parse error in {$file}: " . $e->getMessage(), 0, $e);
        }
    }

    private function assertConfigArray(array $data, string $file): void
    {
        if (!is_array($data)) {
            throw new RuntimeException("Config file must return an array: {$file}");
        }
        foreach ($data as $k => $_) {
            if (!is_string($k)) {
                throw new RuntimeException("Invalid config keys in {$file}: only string keys allowed.");
            }
        }
    }

    // ---------------- Freshness helpers ----------------

    /**
     * @return array{0: array<int,string>, 1: string} [files, hash]
     */
    private function computeConfigFilesHash(): array
    {
        $files = glob(rtrim($this->configPath, '/\\') . '/*.{php,json,yml,yaml}', GLOB_BRACE) ?: [];
        $parts = [];
        foreach ($files as $f) {
            if (is_file($f)) {
                $stat = @stat($f);
                $mtime = $stat['mtime'] ?? filemtime($f);
                $size  = $stat['size']  ?? filesize($f);
                $parts[] = $f . '|' . $mtime . '|' . $size;
            } else {
                $parts[] = $f . '|missing';
            }
        }
        $hash = sha1(implode(';', $parts));
        return [$files, $hash];
    }

    // ---------------- Array helpers ----------------

    private function dataGet(array $target, string $key, mixed $default = null): mixed
    {
        foreach (explode('.', $key) as $seg) {
            if (!is_array($target) || !array_key_exists($seg, $target)) {
                return $default;
            }
            $target = $target[$seg];
        }
        return $target;
    }

    private function dataSet(array &$target, string $key, mixed $value): void
    {
        $ref = &$target;
        foreach (explode('.', $key) as $seg) {
            if (!isset($ref[$seg]) || !is_array($ref[$seg])) {
                $ref[$seg] = [];
            }
            $ref = &$ref[$seg];
        }
        $ref = $value;
    }
}
