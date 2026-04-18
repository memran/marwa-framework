<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

/**
 * Configuration manager for loading and accessing config files.
 *
 * Supports loading PHP config files that return arrays,
 * and retrieving values using dot notation with type safety.
 */
final class Config
{
    /**
     * @var array<string, mixed>
     */
    private array $items = [];

    /**
     * @var list<string>
     */
    private array $loadedFiles = [];

    public function __construct(public string $basePath) {}

    /**
     * Load a configuration file.
     * @param string $filePath Relative or absolute path to config file.
     * @throws \InvalidArgumentException If file does not exist.
     * @throws \RuntimeException If file has already been loaded.
     * @throws \TypeError If file does not return an array.
     *
     */
    public function load(string $filePath): void
    {
        $this->basePath = rtrim($this->basePath, DIRECTORY_SEPARATOR);
        $filePath = $this->basePath . DIRECTORY_SEPARATOR . ltrim($filePath, DIRECTORY_SEPARATOR);

        if (!is_file($filePath)) {
            throw new \InvalidArgumentException("Config file not found: {$filePath}");
        }

        $key = pathinfo($filePath, PATHINFO_FILENAME);

        if (in_array($key, $this->loadedFiles, true)) {
            throw new \RuntimeException("Config file '{$key}' already loaded");
        }

        $content = require $filePath;

        if (!is_array($content)) {
            throw new \TypeError("Config file '{$key}' must return array");
        }

        $this->items[$key] = $content;
        $this->loadedFiles[] = $key;
    }

    /**
     * Load a configuration file when present and skip duplicates.
     */
    public function loadIfExists(string $filePath): bool
    {
        $fullPath = rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($filePath, DIRECTORY_SEPARATOR);
        $key = pathinfo($fullPath, PATHINFO_FILENAME);

        if ($this->isLoaded($key) || !is_file($fullPath)) {
            return false;
        }

        $this->load($filePath);

        return true;
    }

    /**
     * Prime the repository from an already-built config cache payload.
     *
     * @param array<string, mixed> $items
     */
    public function prime(array $items): void
    {
        foreach ($items as $key => $value) {
            if (!is_array($value)) {
                throw new \TypeError('Cached config payload must be an array keyed by config file name.');
            }

            $this->items[$key] = $value;
            if (!in_array($key, $this->loadedFiles, true)) {
                $this->loadedFiles[] = $key;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get a configuration value using dot notation.
     * @param string $key Dot notation key (e.g., "file.key.subkey").
     * @param mixed $default Default value if key not found.
     * @return mixed The configuration value or default.
     */
    public function get(string $key, $default = null): mixed
    {
        $parts = explode('.', $key);
        $fileKey = array_shift($parts);

        if (!isset($this->items[$fileKey])) {
            return $default;
        }

        $value = $this->items[$fileKey];

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    // Type-safe getters
    /**
     * Get a string configuration value.
     * @param string $key Dot notation key.
     * @param string|null $default Default value if key not found.
     * @return string The configuration value.
     */
    public function getString(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            throw new \InvalidArgumentException("Config key '{$key}' is required but null");
        }

        if (!is_string($value)) {
            throw new \TypeError("Config key '{$key}' must be string, got " . gettype($value));
        }

        return $value;
    }

    /**
     * Get an integer configuration value.
     * @param string $key Dot notation key.
     * @param int|null $default Default value if key not found.
     * @return int The configuration value.
     */
    public function getInt(string $key, ?int $default = null): int
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            throw new \InvalidArgumentException("Config key '{$key}' is required but null");
        }

        if (!is_numeric($value)) {
            throw new \TypeError("Config key '{$key}' must be numeric, got " . gettype($value));
        }

        return (int)$value;
    }

    /**
     * Get a boolean configuration value.
     * @param string $key Dot notation key.
     * @param bool|null $default Default value if key not found.
     * @return bool The configuration value.
     */
    public function getBool(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            throw new \InvalidArgumentException("Config key '{$key}' is required but null");
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Get an array configuration value.
     * @param string $key Dot notation key.
     * @param array<mixed>|null $default Default value if key not found.
     * @return array<mixed> The configuration value.
     */
    public function getArray(string $key, ?array $default = null): array
    {
        $value = $this->get($key, $default);

        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new \TypeError("Config key '{$key}' must be array, got " . gettype($value));
        }

        return $value;
    }

    /**
     * Check if a configuration key exists.
     * @param string $key Dot notation key.
     * @return bool True if key exists, false otherwise.
     */
    public function has(string $key): bool
    {
        $parts = explode('.', $key);
        $fileKey = array_shift($parts);

        if (!array_key_exists($fileKey, $this->items)) {
            return false;
        }

        $value = $this->items[$fileKey];

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return false;
            }

            $value = $value[$part];
        }

        return true;
    }

    public function isLoaded(string $key): bool
    {
        return in_array($key, $this->loadedFiles, true);
    }

    /**
     * @return list<string>
     */
    public function getLoadedFiles(): array
    {
        return $this->loadedFiles;
    }

    /**
     * Set a configuration value using dot notation.
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$this->items;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }
    }
}
