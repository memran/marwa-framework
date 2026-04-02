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
### `config_path(string $path = ''): string`
### `routes_path(string $path = ''): string`
### `resources_path(string $path = ''): string`
### `storage_path(string $path = ''): string`
### `module_path(string $path = ''): string`

```php
$configFile = config_path('app.php');
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

## HTTP and Rendering

### `response(string $body = '', int $status = 200): ResponseInterface`

Creates a basic HTML response.

### `router(): mixed`

Returns the router adapter from the container.

### `view(string $tplName = '', array $params = []): mixed`

```php
return view('welcome', ['name' => 'Marwa']);
```

Returns the view adapter or renders a template directly.

## Events and Logging

### `event(AbstractEvent $event): void`

Dispatches a framework event.

### `logger(): LoggerInterface`

Returns the current logger instance.

### `debugger(): mixed`

Returns the debug bar instance when enabled, otherwise `null`.

## Utility Helpers

### `generate_key(int $length = 32, bool $asHex = true): string`

Generates cryptographically secure random bytes or a hex-encoded key.

### `with(mixed $value, callable $callback): mixed`
### `tap(mixed $value, callable $callback): mixed`

Utility helpers for fluent transformation and side effects.

### `dd(mixed ...$vars): never`

Dumps values and terminates execution. Use only for local debugging.
