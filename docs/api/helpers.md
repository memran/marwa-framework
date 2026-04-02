# Helper Functions

The project exposes global helpers through `src/Supports/Helpers.php`.

## Application and Paths

### `app(?string $abstract = null): mixed`

```php
$app = app();
$logger = app(\Marwa\Framework\Adapters\Logger\LoggerAdapter::class);
```

Returns the application instance or resolves a service from the container.

### `base_path(string $path = ''): string`
### `bootstrap_path(string $path = ''): string`
### `cache_path(string $path = ''): string`
### `config_path(string $path = ''): string`
### `database_path(string $path = ''): string`
### `logs_path(string $path = ''): string`
### `public_path(string $path = ''): string`
### `routes_path(string $path = ''): string`
### `resources_path(string $path = ''): string`
### `storage_path(string $path = ''): string`
### `module_path(string $path = ''): string`
### `view_path(string $path = ''): string`

```php
$configFile = config_path('app.php');
$migrationPath = database_path('migrations');
$compiledConfig = bootstrap_path('cache/config.php');
```

Return absolute paths relative to the application base path.

## Configuration and Environment

### `config(string $key, mixed $default = null): mixed`

```php
$timezone = config('app.timezone', 'UTC');
```

Reads a dot-notated config value.

### `env(string $key, mixed $default = null): mixed`

```php
$debug = env('APP_DEBUG', false);
```

Reads environment values with basic type normalization for booleans, numbers, `null`, and empty strings.

### `cache(?string $key = null, mixed $default = null): mixed`

```php
cache()->put('settings.theme', 'light', 300);
$theme = cache('settings.theme', 'dark');
```

Returns the shared cache service or reads one cached value.

## HTTP and Rendering

### `response(string $body = '', int $status = 200): ResponseInterface`

Creates a basic HTML response.

### `router(): mixed`

Returns the router adapter from the container.

### `db(): \Marwa\DB\Connection\ConnectionManager`

Returns the shared `marwa-db` connection manager after bootstrap.

### `session(?string $key = null, mixed $default = null): mixed`

```php
$session = session();
session()->set('user_id', 42);
session()->flash('status', 'Saved');
$userId = session('user_id');
```

Returns the encrypted session service or reads one session value.

### `image(?string $path = null): \Marwa\Framework\Supports\Image`

```php
$thumb = image(public_path('images/photo.jpg'))
    ->fit(320, 180)
    ->save(storage_path('cache/thumb.jpg'));
```

Returns a GD-backed image instance from disk, or a blank 1x1 canvas when no path is provided.

### `view(string $tplName = '', array $params = []): mixed`

```php
return view('welcome', ['name' => 'Marwa']);
```

Returns the view adapter or renders a template directly.

## Events and Logging

### `event(AbstractEvent $event): void`

Dispatches a framework event.

### `dispatch(object $event): object`

Dispatches any object event through the shared application dispatcher and returns the same event instance.

### `logger(): LoggerInterface`

Returns the current logger instance.

### `debugger(): mixed`

Returns the debug bar instance when enabled, otherwise `null`.

### `module(string $slug): \Marwa\Module\ModuleHandle`
### `has_module(string $slug): bool`

```php
if (has_module('blog')) {
    $blog = module('blog');
}
```

Read the active module runtime from the application.

### `is_local(): bool`
### `is_production(): bool`
### `running_in_console(): bool`

Helpers for common environment and runtime checks.

## Utility Helpers

### `generate_key(int $length = 32, bool $asHex = true): string`

Generates cryptographically secure random bytes or a hex-encoded key.

### `with(mixed $value, callable $callback): mixed`
### `tap(mixed $value, callable $callback): mixed`

Utility helpers for fluent transformation and side effects.

### `dd(mixed ...$vars): never`

Dumps values and terminates execution. Use only for local debugging.
