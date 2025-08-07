<?php

namespace Marwa\Application\Configs;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Dotenv\Dotenv;

class Config
{
    protected array $items = [];
    protected bool $loaded = false;
    protected string $cacheFile;

    public function __construct(
        protected string $configPath,
        protected bool $useCache = true,
        ?string $cacheFile = null
    ) {
        $this->cacheFile = $cacheFile ?? '/storage/cache/config.cache.php';
    }

    /**
     * Load configuration files (PHP, JSON, YAML).
     */
    public function load(): void
    {
        if ($this->useCache && file_exists($this->cacheFile)) {
            $this->items = require_once $this->cacheFile;
            $this->loaded = true;
            return;
        }

        $files = glob(rtrim($this->configPath, '/') . '/*.{php,json,yml,yaml}', GLOB_BRACE);

        foreach ($files as $file) {
            $key = basename($file, '.' . pathinfo($file, PATHINFO_EXTENSION));
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            switch ($ext) {
                case 'php':
                    $this->items[$key] = require $file;
                    break;
                case 'json':
                    $this->items[$key] = json_decode(file_get_contents($file), true);
                    break;
                case 'yml':
                case 'yaml':
                    $this->items[$key] = Yaml::parseFile($file);
                    break;
            }
        }

        $this->loaded = true;

        if ($this->useCache) {
            $this->cache();
        }
    }

    /**
     * Save the config array into a PHP cache file.
     */
    public function cache(): void
    {
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $export = var_export($this->items, true);
        file_put_contents($this->cacheFile, "<?php\nreturn $export;");
    }

    /**
     * Get config value (supports dot notation).
     */
    public function get(string $key = null, mixed $default = null): mixed
    {
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

    /**
     * Set a config value using dot notation.
     */
    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &$this->items;

        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        $ref = $value;
    }

    /**
     * Check if a config key exists.
     */
    public function has(string $key): bool
    {
        return $this->get($key, '__not_found__') !== '__not_found__';
    }

    /**
     * Get all configuration items.
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Load environment variables from .env file.
     */
    public function loadEnv(string $envPath): void
    {
        if (file_exists($envPath . '/.env')) {
            $dotenv = new Dotenv();
            $dotenv->loadEnv($envPath . '/.env');
        }
    }

    /**
     * Get environment variable with default fallback.
     */
    public function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Override cache file location if needed.
     */
    public function setCacheFile(string $path): void
    {
        $this->cacheFile = $path;
    }
}
