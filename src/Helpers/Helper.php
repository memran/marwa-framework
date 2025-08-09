<?php

declare(strict_types=1);

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Marwa\App\Core\Container;
use Marwa\App\Facades\App;

/**
 * Dump one or more variables without stopping execution.
 *
 * @param mixed ...$vars
 * @return void
 */
if (!function_exists('d')) {
    function d(mixed ...$vars): void
    {
        static $cloner = null;
        static $dumper = null;

        if ($cloner === null) {
            $cloner = new VarCloner();
        }

        if ($dumper === null) {
            if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
                $dumper = new CliDumper();
                // enable colors if output is a TTY
                $isTty = function_exists('stream_isatty') && defined('STDOUT') ? @stream_isatty(STDOUT) : false;
                $dumper->setColors($isTty);
            } else {
                if (!headers_sent()) {
                    header('Content-Type: text/html; charset=UTF-8');
                }
                $dumper = new HtmlDumper();
            }
        }

        foreach ($vars as $var) {
            $dumper->dump($cloner->cloneVar($var));
        }
    }
}

/**
 * Dump one or more variables and terminate the script (dump & die).
 *
 * @param mixed ...$vars
 * @return never
 */
if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        if (!$vars) {
            // If no args, show a small stack context for convenience
            $vars = [new RuntimeException('dd() called')];
        }

        d(...$vars);

        // ensure output is flushed before exiting
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }

        exit(1);
    }
}

if (!function_exists('dump')) {
    // Alias for d() if dump() already exists
    function dump(...$vars): void
    {
        d(...$vars);
    }
}

if (!function_exists('isStaticMethod')) {
    /**
     * Check if a given class has a specific static method.
     *
     * @param string $className Fully qualified class name
     * @param string $methodName Method name to check
     * @return bool True if method exists and is static, false otherwise
     */
    function isStaticMethod(string $className, string $methodName): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        if (!method_exists($className, $methodName)) {
            return false;
        }

        try {
            $reflection = new ReflectionMethod($className, $methodName);
            return $reflection->isStatic();
        } catch (ReflectionException $e) {
            return false;
        }
    }
}


if (!function_exists('base_path')) {
    /**
     * Get the base path of the application.
     *
     * @return string
     */
    function base_path(): string
    {
        return defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    }
}

if (!function_exists('private_storage')) {
    /**
     * Get the base path of the application.
     *
     * @return string
     */
    function private_storage(): string
    {
        return defined('STORAGE_PATH') ? STORAGE_PATH : dirname(__DIR__, 2);
    }
}

if (!function_exists('public_storage')) {
    /**
     * Get the base path of the application.
     *
     * @return string
     */
    function public_storage(): string
    {
        return defined('PUBLIC_STORAGE') ? BASE_PATH : dirname(__DIR__, 2);
    }
}

if (!function_exists('log_path')) {
    /**
     * Get the log path of the application.
     *
     * @return string
     */
    function log_path(): string
    {
        return defined('LOG_PATH') ? LOG_PATH : base_path() . '/storage/logs';
    }
}
/**
 * Application helper function to access the container.
 *
 * @param string|null $id Optional service ID to retrieve from the container.
 * @return \Marwa\App\Core\Container
 */
if (!function_exists('app')) {
    function app($id = null): mixed
    {

        if ($id !== null) {
            //return \Marwa\App\Core\Container::getInstance()->get($id);
            return  App::get($id);
        }
        return App::getInstance();
        return Container::getInstance();
    }
}

if (!function_exists('config')) {
    /**
     * Laravel-style config() helper.
     *
     * Usage:
     *   config('app.name');
     *   config('database.connections.mysql.host', '127.0.0.1');
     *   config()->set('custom.key', 'value'); // returns Config instance if no key
     */
    function config(string $key, mixed $default = null): mixed
    {
        $config = app('config');

        if ($config === null) {
            throw new RuntimeException('Config not initialized. Ensure you have booted the application.');
        }

        return $config->get($key, $default);
    }
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
if (!function_exists('env')) {
    /**
     * Get an environment variable with optional default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        //dd(getenv(), $_ENV);
        return getenv($key) === null ? $_ENV[$key] : getenv($key);
    }
}


if (!function_exists('abort')) {
    /**
     * Throw an HTTP exception with a given status code.
     *
     * @param int $code
     * @param string|null $message
     * @throws \Marwa\App\Exceptions\HttpException
     */
    function abort(int $code, ?string $message = null): void
    {
        throw new \Marwa\App\Exceptions\HttpException($code, $message);
    }
}
if (!function_exists('abort_if')) {
    /**
     * Throw an HTTP exception if the condition is true.
     *
     * @param bool $condition
     * @param int $code
     * @param string|null $message
     * @throws \Marwa\App\Exceptions\HttpException
     */
    function abort_if(bool $condition, int $code, ?string $message = null): void
    {
        if ($condition) {
            abort($code, $message);
        }
    }
}
if (!function_exists('abort_unless')) {
    /**
     * Throw an HTTP exception unless the condition is true.
     *
     * @param bool $condition
     * @param int $code
     * @param string|null $message
     * @throws \Marwa\App\Exceptions\HttpException
     */
    function abort_unless(bool $condition, int $code, ?string $message = null): void
    {
        if (!$condition) {
            abort($code, $message);
        }
    }
}
if (!function_exists('resolve')) {
    /**
     * Resolve a service from the container.
     *
     * @param string $abstract
     * @param bool $new
     * @return mixed
     */
    function resolve(string $abstract, ?bool $new = false): mixed
    {
        return app()->make($abstract, $new);
    }
}
if (!function_exists('container')) {
    /**
     * Get the application container instance.
     *
     * @return \Marwa\App\Core\Container
     */
    function container(): \Marwa\App\Core\Container
    {
        return app();
    }
}
if (!function_exists('singleton')) {
    /**
     * Bind a service as a singleton in the container.
     *
     * @param string $abstract
     * @param mixed|null $concrete
     * @return \Marwa\App\Core\Container
     */
    function singleton(string $abstract, mixed $concrete = null): \Marwa\App\Core\Container
    {
        return app()->singleton($abstract, $concrete);
    }
}
if (!function_exists('bind')) {
    /**
     * Bind a service in the container.
     * @param string $abstract
     * @param mixed|null $concrete
     * @return \Marwa\App\Core\Container
     */
    function bind(string $abstract, mixed $concrete = null): \Marwa\App\Core\Container
    {
        return app()->bind($abstract, $concrete);
    }
}
if (!function_exists('has')) {
    /**
     * Check if a service is bound in the container.
     *  * @param string $abstract
     * @return bool
     */
    function has(string $abstract): bool
    {
        return app()->has($abstract);
    }
}
if (!function_exists('response')) {
    /**
     * Get the response instance.
     *
     * @return \Marwa\App\Core\Response
     */
    function response(): \Marwa\App\Core\Response
    {
        return app()->get('response');
    }
}
if (!function_exists('request')) {
    /**
     * Get the request instance.
     * @return \Marwa\App\Core\Request
     */
    function request(): \Marwa\App\Core\Request
    {
        return app()->get('request');
    }
}
if (!function_exists('route')) {
    /**
     * Get the router instance.
     * @return \Marwa\App\Core\Router
     */
    function route(): \Marwa\App\Core\Router
    {
        return app()->get('router');
    }
}
if (!function_exists('logger')) {
    /**
     * Get the logger instance.
     * @return \Marwa\App\Logging\Logger
     */
    function logger(): \Marwa\App\Logging\Logger
    {
        return app()->get('logger');
    }
}
if (!function_exists('error_handler')) {
    /**
     * Get the error handler instance.
     * @return \Marwa\Logging\ErrorHandler  
     */
    function error_handler(): \Marwa\Logging\ErrorHandler
    {
        return app()->get('error_handler');
    }
}
