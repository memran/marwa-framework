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
     * @var array<mixed> 
     */
    private array $items = [];
    /**
     * @var array<mixed> 
     */
    private array $loadedFiles = [];

    /**
     *  
     */
    public function __construct(public  string $basePath) {}

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

        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Config file not found: {$filePath}");
        }

        $key = pathinfo($filePath, PATHINFO_FILENAME);

        if (in_array($key, $this->loadedFiles)) {
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

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get an array configuration value.
     * @param string $key Dot notation key.
     * @param array|null $default Default value if key not found.
     * @return array The configuration value.
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
        return $this->get($key) !== null;
    }

    /**
     * Get list of loaded configuration files.
     * @return array List of loaded config file keys.
     */
    public function getLoadedFiles(): array
    {
        return $this->loadedFiles;
    }
}
