<?php

declare(strict_types=1);


use Marwa\Framework\Adapters\Event\AbstractEvent;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Marwa\Framework\Adapters\Logger\LoggerAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Marwa\Router\Response;
use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Adapters\ViewAdapter;
use Psr\Log\LoggerInterface;

/**
 * Resolve the current Application instance from a global static.
 * In your app's bootstrap (e.g., public/index.php), set it like:
 *   $GLOBALS['marwa_app'] = $app;
 */
function app(?string $abstract = null): mixed
{
    /** @var Application $app */
    $app = $GLOBALS['marwa_app'] ?? null;
    if (!$app instanceof Application) {
        throw new RuntimeException('Application instance not set. Assign $GLOBALS["marwa_app"] = $app at bootstrap.');
    }
    return $abstract ? $app->make($abstract) : $app;
}

function base_path(string $path = ''): string
{
    /** @var Application $app */
    $app = app();
    return rtrim($app->getBasePath() . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''), DIRECTORY_SEPARATOR);
}

/**
 * Route Path
 * @string 
 */
function routes_path()
{
    return base_path("routes");
}

/**
 * Storage Path
 * @return string
 */
function storage_path()
{
    return base_path("storage");
}
/**
 * Config Path
 * @return string
 */
function config_path()
{
    return base_path('config');
}
/**
 * Resources Path
 * @return string
 */
function resources_path()
{
    return base_path("resources");
}
/**
 * Module Path
 * @return string
 */
function module_path()
{
    return base_path("modules");
}
/** Get config value using "file.key.sub" dot notation. */
function config(string $key, mixed $default = null): mixed
{
    /** @var Config $repo */
    return app()->make(Config::class)->get($key, $default);
}

/** Get environment variable with default. */

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        static $loaded = false;

        if (!$loaded && file_exists('.env')) {
            $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;

                [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
                $_ENV[trim($name)] = trim($value);
            }
            $loaded = true;
        }

        $value = $_ENV[$key] ?? $default;

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => is_numeric($value) ? (strpos($value, '.') ? (float) $value : (int) $value) : $value,
        };
    }
}

/** Convenience: create an empty response via HttpFactoryInterface. */
function response(string $body = '', int $status = 200): ResponseInterface
{
    return Response::html($body, $status);
}

/** Dispatch an event if dispatcher is bound. */
function event(AbstractEvent $event): void
{

    /** @var EventDispatcherInterface $bus */
    $bus = app(EventDispatcherAdapter::class);
    $bus->dispatch($event);
}

function logger(): LoggerInterface
{
    return app(LoggerAdapter::class);
}
function router(): mixed
{
    return app(RouterAdapter::class);
}

function view(string $tplName = '', array $params = []): mixed
{
    if ($tplName != null)
        return app(ViewAdapter::class)->render($tplName, $params);
    else
        return app(ViewAdapter::class);
}

function debugger(): mixed
{
    if (env('DEBUGBAR_ENABLED', false)) {
        if (app()->has('debugbar')) {
            return app('debugbar');
        }
    }

    return null;
}

if (!function_exists('generate_key')) {
    /**
     * Generate a cryptographically secure random key.
     *
     * @param int    $length    The length of the key (in bytes, default: 32)
     * @param bool   $asHex     Whether to return the key as a hex string
     * @return string           The generated key
     *
     * @throws RuntimeException If secure random bytes could not be generated
     */
    function generate_key(int $length = 32, bool $asHex = true): string
    {
        if ($length <= 0) {
            throw new InvalidArgumentException('Key length must be greater than 0.');
        }

        try {
            $randomBytes = random_bytes($length);
        } catch (Exception $e) {
            throw new RuntimeException('Unable to generate a secure key.', 0, $e);
        }

        return $asHex ? bin2hex($randomBytes) : $randomBytes;
    }
}


if (!function_exists('with')) {
    /**
     * Return the given value, optionally passing it to a callback.
     *
     * @template T
     * @param  T  $value
     * @param  callable  $callback
     * @return T
     */
    function with($value, callable $callback)
    {
        if ($callback) {
            return $callback($value) ?? $value;
        }

        return $value;
    }
}

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value and then return the value.
     *
     * This is useful for performing side effects without breaking method chains.
     *
     * @template T
     * @param  T  $value
     * @param  callable  $callback
     * @return T
     */
    function tap($value, callable $callback)
    {
        if ($callback) {
            $callback($value);
        }

        return $value;
    }
}


if (!function_exists('dd')) {
    function dd(...$vars): never
    {
        foreach ($vars as $var) {
            var_dump($var);
        }

        exit(1);
    }
}
