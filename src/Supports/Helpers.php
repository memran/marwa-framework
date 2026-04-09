<?php

declare(strict_types=1);

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Adapters\Event\AbstractEvent;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Marwa\Framework\Adapters\Logger\LoggerAdapter;
use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Contracts\HttpClientInterface;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Contracts\NotificationInterface;
use Marwa\Framework\Contracts\SecurityInterface;
use Marwa\Framework\Contracts\SessionInterface;
use Marwa\Framework\Notifications\NotificationManager;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Supports\Image as ImageSupport;
use Marwa\Framework\Supports\Mailer;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Supports\Storage as StorageSupport;
use Marwa\Framework\Validation\RequestValidator;
use Marwa\Framework\Validation\ValidationException;
use Marwa\Framework\Views\View as FrameworkView;
use Marwa\Module\ModuleHandle;
use Marwa\Router\Http\Input;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolve the current Application instance from a global static.
 * In your app's bootstrap (e.g., public/index.php), set it like:
 *   $GLOBALS['marwa_app'] = $app;
 */
function app(?string $abstract = null): mixed
{
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
function routes_path(string $path = ''): string
{
    return base_path('routes' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

/**
 * Storage Path
 * @return string
 */
function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}
/**
 * Config Path
 * @return string
 */
function config_path(string $path = ''): string
{
    return base_path('config' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}
/**
 * Resources Path
 * @return string
 */
function resources_path(string $path = ''): string
{
    return base_path('resources' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}
/**
 * Module Path
 * @return string
 */
function module_path(string $path = ''): string
{
    return base_path('modules' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function database_path(string $path = ''): string
{
    return base_path('database' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function public_path(string $path = ''): string
{
    return base_path('public' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function bootstrap_path(string $path = ''): string
{
    return base_path('bootstrap' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function cache_path(string $path = ''): string
{
    return storage_path('cache' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function logs_path(string $path = ''): string
{
    return storage_path('logs' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function view_path(string $path = ''): string
{
    return resources_path('views' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}
/** Get config value using "file.key.sub" dot notation. */
function config(string $key, mixed $default = null): mixed
{
    return app()->make(Config::class)->get($key, $default);
}

function cache(?string $key = null, mixed $default = null): mixed
{
    /** @var CacheInterface $cache */
    $cache = app(CacheInterface::class);

    if ($key !== null) {
        return $cache->get($key, $default);
    }

    return $cache;
}

function http(): HttpClientInterface
{
    /** @var HttpClientInterface $http */
    $http = app(HttpClientInterface::class);

    return $http;
}

function storage(?string $disk = null): StorageSupport
{
    /** @var StorageSupport $storage */
    $storage = app(StorageSupport::class);

    return $disk !== null ? $storage->disk($disk) : $storage;
}

/** Get environment variable with default. */

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        static $loadedPaths = [];

        $envPath = isset($GLOBALS['marwa_app']) && $GLOBALS['marwa_app'] instanceof Application
            ? base_path('.env')
            : getcwd() . DIRECTORY_SEPARATOR . '.env';

        if (!isset($loadedPaths[$envPath]) && is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
                $name = trim($name);
                $value = trim(preg_replace('/\s+#.*$/', '', $value) ?? $value);
                $value = trim($value, " \t\n\r\0\x0B\"'");

                if ($name === '') {
                    continue;
                }

                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }

            $loadedPaths[$envPath] = true;
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => is_numeric($value) ? (str_contains($value, '.') ? (float) $value : (int) $value) : $value,
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
    $bus = app(EventDispatcherAdapter::class);

    if (!$bus instanceof EventDispatcherInterface) {
        throw new RuntimeException('Event dispatcher binding is invalid.');
    }

    $bus->dispatch($event);
}

function logger(): LoggerInterface
{
    return app(LoggerAdapter::class);
}

function mailer(): MailerInterface
{
    return app(Mailer::class);
}

function security(): SecurityInterface
{
    return app(SecurityInterface::class);
}

function notification(): NotificationManager
{
    return app(NotificationManager::class);
}

function csrf_token(): string
{
    return security()->csrfToken();
}

function csrf_field(): string
{
    return security()->csrfField();
}

function validate_csrf_token(string $token): bool
{
    return security()->validateCsrfToken($token);
}

function is_trusted_host(string $host): bool
{
    return security()->isTrustedHost($host);
}

function is_trusted_origin(string $origin): bool
{
    return security()->isTrustedOrigin($origin);
}

function throttle(string $key, ?int $limit = null, ?int $window = null): bool
{
    return security()->throttle($key, $limit, $window);
}

function sanitize_filename(string $name): string
{
    return security()->sanitizeFilename($name);
}

function safe_path(string $path, string $basePath): string
{
    return security()->safePath($path, $basePath);
}

function router(): mixed
{
    return app(RouterAdapter::class);
}

function module(string $slug): ModuleHandle
{
    return app()->module($slug);
}

function has_module(string $slug): bool
{
    return app()->hasModule($slug);
}

function db(): ConnectionManager
{
    /** @var ConnectionManager $manager */
    $manager = app(ConnectionManager::class);

    return $manager;
}

function session(?string $key = null, mixed $default = null): mixed
{
    /** @var SessionInterface $session */
    $session = app(SessionInterface::class);

    if ($key !== null) {
        return $session->get($key, $default);
    }

    return $session;
}

function request(?string $key = null, mixed $default = null): mixed
{
    /** @var ServerRequestInterface $request */
    $request = app(ServerRequestInterface::class);

    if ($key === null) {
        return $request;
    }

    return Input::get($key, $default);
}

/**
 * @param array<string, mixed> $rules
 * @param array<string, mixed> $messages
 * @param array<string, string> $attributes
 * @return array<string, mixed>
 */
function validate_request(
    array $rules,
    array $messages = [],
    array $attributes = [],
    ?ServerRequestInterface $request = null
): array {
    /** @var RequestValidator $validator */
    $validator = app(RequestValidator::class);

    return $validator->validateRequest($request ?? request(), $rules, $messages, $attributes);
}

/**
 * @param array<string, mixed>|null $default
 */
function old(?string $key = null, mixed $default = null): mixed
{
    $data = session(ValidationException::OLD_INPUT_KEY, []);

    if (!is_array($data)) {
        $data = [];
    }

    if ($key === null) {
        return $data;
    }

    if (array_key_exists($key, $data)) {
        return $data[$key];
    }

    $current = $data;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($current) || !array_key_exists($segment, $current)) {
            return $default;
        }

        $current = $current[$segment];
    }

    return $current;
}

function image(?string $path = null): ImageSupport
{
    if ($path !== null && trim($path) !== '') {
        return ImageSupport::fromFile($path);
    }

    return ImageSupport::canvas(1, 1, '#00000000');
}

/**
 * @param array<string, mixed> $params
 */
function view(string $tplName = '', array $params = []): mixed
{
    /** @var FrameworkView $view */
    $view = app(FrameworkView::class);

    if ($tplName !== '') {
        return $view->make($tplName, $params);
    }

    return $view;
}

function debugger(): mixed
{
    if (config('app.debugbar', false)) {
        if (app()->has('debugbar')) {
            return app('debugbar');
        }
    }

    return null;
}

function is_local(): bool
{
    $app = app();

    return $app->environment('local') === true || $app->environment('development') === true;
}

function is_production(): bool
{
    return app()->environment('production') === true;
}

function running_in_console(): bool
{
    return Runtime::isConsole();
}

function dispatch(object $event): object
{
    return app()->dispatch($event);
}

/**
 * @return array<string, mixed>
 */
function notify(NotificationInterface $notification, ?object $notifiable = null): array
{
    return notification()->send($notification, $notifiable);
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
        return $callback($value) ?? $value;
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
        $callback($value);

        return $value;
    }
}


if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            var_dump($var);
        }

        exit(1);
    }
}
