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

### `http(): \Marwa\Framework\Contracts\HttpClientInterface`

```php
http()->withClient('github')->get('/repos/memran/marwa-framework');
```

Returns the shared Guzzle-backed HTTP client service.

### `notification(): \Marwa\Framework\Notifications\NotificationManager`

```php
notification()->send(new App\Notifications\OrderShipped());
```

Returns the shared notification manager.

### `notify(\Marwa\Framework\Contracts\NotificationInterface $notification, ?object $notifiable = null): array`

```php
notify(new App\Notifications\OrderShipped(), $user);
```

Sends a notification through the configured channels and returns channel results.

### `mailer(): \Marwa\Framework\Contracts\MailerInterface`

```php
mailer()
    ->to('user@example.com', 'User')
    ->subject('Welcome')
    ->html('<p>Hello</p>')
    ->send();
```

Returns the shared SwiftMailer-compatible mail service.

Queue mail with a mailable class:

```php
mailer()->queue(new App\Mail\WelcomeMail(['subject' => 'Welcome']));
```

## HTTP and Rendering

### `response(string $body = '', int $status = 200): ResponseInterface`

Creates a basic HTML response.

### `router(): mixed`

Returns the router adapter from the container.

### `http(): \Marwa\Framework\Contracts\HttpClientInterface`

Returns the shared outbound HTTP client service.

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

### `request(?string $key = null, mixed $default = null): mixed`

```php
$request = request();
$title = request('title');
```

Returns the current PSR-7 request when called without a key, or the current input value when a key is provided.

### `validate_request(array $rules, array $messages = [], array $attributes = [], ?ServerRequestInterface $request = null): array`

```php
$data = validate_request([
    'title' => 'required|string|min:3',
    'published' => 'boolean',
]);
```

Validates the current request or the request you pass explicitly and returns normalized data.

### `old(?string $key = null, mixed $default = null): mixed`

```php
$title = old('title', '');
```

Reads flashed validation input from the session after a failed validation response.

### `image(?string $path = null): \Marwa\Framework\Supports\Image`

```php
$thumb = image(public_path('images/photo.jpg'))
    ->fit(320, 180)
    ->save(storage_path('cache/thumb.jpg'));
```

Returns a GD-backed image instance from disk, or a blank 1x1 canvas when no path is provided.

### `storage(?string $disk = null): \Marwa\Framework\Supports\Storage`

```php
storage()->write('docs/readme.txt', 'Hello');
$public = storage('public');
```

Returns the shared Flysystem-backed storage manager, optionally scoped to a configured disk.

### `security(): \Marwa\Framework\Contracts\SecurityInterface`

```php
$token = security()->csrfToken();
```

Returns the shared security service.

### `csrf_token(): string`
### `csrf_field(): string`
### `validate_csrf_token(string $token): bool`
### `is_trusted_host(string $host): bool`
### `is_trusted_origin(string $origin): bool`
### `throttle(string $key, ?int $limit = null, ?int $window = null): bool`
### `sanitize_filename(string $name): string`
### `safe_path(string $path, string $basePath): string`

```php
csrf_field();
validate_csrf_token($request->getHeaderLine('X-CSRF-TOKEN'));
```

Helpers for CSRF, trust checks, rate limiting, and safe filesystem access.

### `view(string $tplName = '', array $params = []): mixed`

```php
return view('welcome', ['name' => 'Marwa']);
```

Returns the shared view service or renders a template as an HTML response directly.

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
