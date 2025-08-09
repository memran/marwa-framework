<?php

declare(strict_types=1);

namespace Marwa\App\Configs;

use RuntimeException;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;
use Symfony\Component\Dotenv\Exception\FormatException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class Config
{
    private array $items = [];
    private bool $loaded = false;

    private string $projectRoot;
    private string $configPath;
    private string $envFile;

    private string $cacheFile;
    private string $cacheMode = 'file'; // 'file', 'memory', 'none'
    private bool $autoCache = false;     // Automatically cache after load

    public function __construct(
        ?string $projectRoot = null,
        ?string $configPath = null,
        bool $cache = false,
        string $envFile = '.env',
        ?string $cacheFile = null
    ) {
        $this->projectRoot = rtrim($projectRoot ?? \dirname(__DIR__, 2), '/\\');
        $this->configPath  = rtrim($configPath  ?? ($this->projectRoot . '/config'), '/\\');
        $this->envFile     = $envFile;
        $this->cacheFile   = $cacheFile ?? ($this->projectRoot . '/storage/cache/config.cache.php');
        $this->autoCache = $cache;
    }

    /**
     * Set cache mode: file, memory, none
     */
    public function setCacheMode(string $mode): void
    {
        $valid = ['file', 'memory', 'none'];
        if (!in_array($mode, $valid, true)) {
            throw new RuntimeException("Invalid cache mode. Allowed: " . implode(', ', $valid));
        }
        $this->cacheMode = $mode;
    }

    /**
     * Enable or disable auto-caching after load.
     */
    public function setAutoCache(bool $enabled): void
    {
        $this->autoCache = $enabled;
        $this->loaded = false;
        $this->clearCache();
    }

    /**
     * Force load now (auto-loads .env first, then config files).
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        // Try to load from cache first
        if ($this->cacheMode === 'file' && file_exists($this->cacheFile)) {
            $cached = require $this->cacheFile;
            if (!is_array($cached)) {
                throw new RuntimeException("Invalid cache format in {$this->cacheFile}");
            }
            $this->items  = $cached;
            $this->loaded = true;
            return;
        }

        $this->loadEnv();
        $this->items = $this->loadConfigDirectory($this->configPath);
        $this->loaded = true;

        if ($this->autoCache) {
            $this->cache();
        }
    }

    /**
     * Cache the loaded configuration based on mode.
     */
    public function cache(): void
    {
        if (!$this->loaded) {
            throw new RuntimeException("Cannot cache before config is loaded.");
        }

        switch ($this->cacheMode) {
            case 'none':
                return; // no caching at all

            case 'memory':
                // In-memory means do nothing — data already lives in $this->items
                return;

            case 'file':
                $dir = dirname($this->cacheFile);
                if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                    throw new RuntimeException("Failed to create cache directory: {$dir}");
                }
                $export = var_export($this->items, true);
                if (file_put_contents($this->cacheFile, "<?php\nreturn {$export};") === false) {
                    throw new RuntimeException("Failed to write cache file: {$this->cacheFile}");
                }
                return;
        }
    }

    /**
     * Clear file cache.
     */
    public function clearCache(): void
    {
        if ($this->cacheMode === 'file' && file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();
        if (is_null($key)) {
            return $this->items;
        }

        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $this->env($key, $default);
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $this->load();
        $segments = explode('.', $key);
        $ref = &$this->items;
        foreach ($segments as $seg) {
            if (!isset($ref[$seg]) || !is_array($ref[$seg])) {
                $ref[$seg] = [];
            }
            $ref = &$ref[$seg];
        }
        $ref = $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key, "__missing__") !== "__missing__";
    }

    public function all(): array
    {
        $this->load();
        return $this->items;
    }
    /**
     * Get environment variable with default fallback.
     */
    public function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }

    // ---------------------------
    // Internals
    // ---------------------------

    private function loadEnv(): void
    {
        $envPath = $this->projectRoot . DIRECTORY_SEPARATOR . $this->envFile;

        if (!is_file($envPath)) {
            return;
        }

        $dotenv = new Dotenv();
        try {
            $dotenv->loadEnv($envPath);
        } catch (PathException $e) {
            throw new RuntimeException("Environment file not found: {$envPath}", 0, $e);
        } catch (FormatException $e) {
            throw new RuntimeException("Invalid .env format in '{$envPath}': " . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new RuntimeException("Failed to load environment from '{$envPath}': " . $e->getMessage(), 0, $e);
        }
    }

    private function loadConfigDirectory(string $dir): array
    {
        if (!is_dir($dir)) {
            throw new RuntimeException("Config directory not found: {$dir}");
        }

        $pattern = rtrim($dir, '/\\') . '/*.{php,json,yml,yaml}';
        $files = glob($pattern, GLOB_BRACE) ?: [];
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
            switch ($ext) {
                case 'php':
                    $data = require_once $file;
                    break;
                case 'json':
                    $json = file_get_contents($file);
                    $data = json_decode((string)$json, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException("JSON error: " . json_last_error_msg());
                    }
                    break;
                case 'yml':
                case 'yaml':
                    $data = Yaml::parseFile($file);
                    break;
                default:
                    throw new RuntimeException("Unsupported config file type: {$file}");
            }
        } catch (ParseException $e) {
            throw new RuntimeException("YAML parse error in '{$file}': " . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new RuntimeException("Failed to parse config file '{$file}': " . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new RuntimeException("Config file must return an array: {$file}");
        }

        return $data;
    }

    private function assertConfigArray(array $data, string $file): void
    {
        foreach ($data as $k => $_) {
            if (!is_string($k)) {
                throw new RuntimeException("Invalid config structure in '{$file}': only string keys are allowed.");
            }
        }
    }
}
