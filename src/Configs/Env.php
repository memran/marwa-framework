<?php

declare(strict_types=1);

namespace Marwa\App\Configs;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;
use Symfony\Component\Dotenv\Exception\FormatException;
use RuntimeException;

final class Env
{
    private bool $loaded = false;
    private string $projectRoot;
    private string $envFile;
    private bool $overload;

    /**
     * @param string|null $projectRoot Defaults to 2 dirs up from current file
     * @param string $envFile          Default .env
     * @param bool   $overload         Whether to overwrite existing env vars
     */
    public function __construct(
        ?string $projectRoot = null,
        string $envFile = '.env',
        bool $overload = false
    ) {
        $this->projectRoot = rtrim($projectRoot ?? \dirname(__DIR__, 2), '/\\');
        $this->envFile     = $envFile;
        $this->overload    = $overload;
    }

    /**
     * Load .env and related files once (Laravel style).
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $envPath = $this->projectRoot . DIRECTORY_SEPARATOR . $this->envFile;
        if (!is_file($envPath)) {
            $this->loaded = true;
            return; // no .env, skip silently
        }

        try {
            $dotenv = new Dotenv();
            if ($this->overload) {
                $dotenv->overload($envPath);
            } else {
                // loadEnv handles .env, .env.local, .env.{APP_ENV}, .env.{APP_ENV}.local
                $dotenv->loadEnv($envPath);
            }
        } catch (PathException $e) {
            throw new RuntimeException("Environment file not found: {$envPath}", 0, $e);
        } catch (FormatException $e) {
            throw new RuntimeException("Invalid .env format in '{$envPath}': " . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new RuntimeException("Failed to load environment from '{$envPath}': " . $e->getMessage(), 0, $e);
        }

        $this->loaded = true;
    }

    /**
     * Get an env variable with Laravel-style casting.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();

        $raw = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($raw === false || $raw === null) {
            return $default;
        }
        return $this->castValue($raw);
    }

    /**
     * Convert dot notation to ENV_KEY style (app.debug -> APP_DEBUG)
     */
    public function getFromDot(string $dotKey, mixed $default = null): mixed
    {
        $envKey = strtoupper(str_replace(['.', ' '], '_', $dotKey));
        return $this->get($envKey, $default);
    }

    /**
     * Laravel-like casting for env values.
     */
    private function castValue(mixed $val): mixed
    {
        if (!is_string($val)) {
            return $val;
        }

        $lower = strtolower(trim($val));

        return match ($lower) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            'empty' => '',
            default => is_numeric($val)
                ? (str_contains($val, '.') ? (float)$val : (int)$val)
                : $val,
        };
    }
}
