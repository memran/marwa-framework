# Helper Functions

The framework exposes global helper functions through modular files in `src/Supports/Helpers/`. The main `Helpers.php` file re-exports all functions for backward compatibility.

## Architecture

Helper functions are organized by category:

```
src/Supports/
├── Helpers/
│   ├── Paths.php             # Path helper functions
│   ├── Container.php         # app(), config(), cache(), storage(), db()
│   ├── SessionRequest.php    # session(), request(), env()
│   ├── Services.php          # event(), logger(), mailer(), router(), http(), etc.
│   ├── Security.php          # security(), csrf_*(), throttle(), etc.
│   ├── ValidationResponse.php # validate_request(), old(), response()
│   ├── ViewDebug.php        # view(), image(), debugger(), is_local(), etc.
│   └── Utilities.php         # generate_key(), with(), tap(), dd()
└── Helpers.php              # Re-exports all helpers
```

## Application and Paths

### `app(?string $abstract = null): mixed`

```php
$app = app();
$logger = app(\Marwa\Framework\Adapters\Logger\LoggerAdapter::class);
```

Returns the application instance or resolves a service from the container.

### Path Functions

| Function | Description |
|----------|-------------|
| `base_path(string $path = '')` | Application root |
| `bootstrap_path(string $path = '')` | Bootstrap directory |
| `cache_path(string $path = '')` | Cache directory |
| `config_path(string $path = '')` | Config directory |
| `database_path(string $path = '')` | Database directory |
| `logs_path(string $path = '')` | Logs directory |
| `module_path(string $path = '')` | Modules directory |
| `public_path(string $path = '')` | Public directory |
| `public_storage_path(string $path = '')` | Public storage directory |
| `resources_path(string $path = '')` | Resources directory |
| `routes_path(string $path = '')` | Routes directory |
| `storage_path(string $path = '')` | Storage directory |
| `view_path(string $path = '')` | Views directory |

```php
$configFile = config_path('app.php');
$migrationPath = database_path('migrations');
$compiledConfig = bootstrap_path('cache/config.php');
```

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

## Container Services

### `cache(?string $key = null, mixed $default = null): mixed`

```php
cache()->put('settings.theme', 'light', 300);
$theme = cache('settings.theme', 'dark');
```

Returns the shared cache service or reads one cached value.

### `db(): \Marwa\DB\Connection\ConnectionManager`

```php
$users = db()->table('users')->get();
```

Returns the shared `marwa-db` connection manager.

### `storage(?string $disk = null): \Marwa\Framework\Supports\Storage`

```php
storage()->write('docs/readme.txt', 'Hello');
$public = storage('public');
```

Returns the shared Flysystem-backed storage manager.

## HTTP and Requests

### `http(): \Marwa\Framework\Contracts\HttpClientInterface`

```php
http()->withClient('github')->get('/repos/memran/marwa-framework');
```

Returns the shared Guzzle-backed HTTP client service.

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

## Notifications and Mail

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

## Validation

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

## Rendering

### `view(string $tplName = '', array $params = []): mixed`

```php
return view('welcome', ['name' => 'Marwa']);
```

Returns the shared view service or renders a template as an HTML response directly.

### `image(?string $path = null): \Marwa\Framework\Supports\Image`

```php
$thumb = image(public_path('images/photo.jpg'))
    ->fit(320, 180)
    ->save(storage_path('cache/thumb.jpg'));
```

Returns a GD-backed image instance from disk, or a blank 1x1 canvas when no path is provided.

### `response(string $body = '', int $status = 200): ResponseInterface`

Creates a basic HTML response.

## Security

### `security(): \Marwa\Framework\Contracts\SecurityInterface`

```php
$token = security()->csrfToken();
```

Returns the shared security service.

### Security Helpers

| Function | Description |
|----------|-------------|
| `csrf_token(): string` | Generate CSRF token |
| `csrf_field(): string` | Generate CSRF hidden field |
| `validate_csrf_token(string $token): bool` | Validate CSRF token |
| `is_trusted_host(string $host): bool` | Check trusted host |
| `is_trusted_origin(string $origin): bool` | Check trusted origin |
| `throttle(string $key, ?int $limit, ?int $window): bool` | Rate limit check |
| `sanitize_filename(string $name): string` | Sanitize filename |
| `safe_path(string $path, string $basePath): string` | Safe path resolution |

```php
csrf_field();
validate_csrf_token($request->getHeaderLine('X-CSRF-TOKEN'));
```

## Events and Logging

### `event(\Marwa\Framework\Adapters\Event\AbstractEvent $event): void`

Dispatches a framework event.

### `dispatch(object $event): object`

Dispatches any object event through the shared application dispatcher and returns the same event instance.

### `logger(): LoggerInterface`

Returns the current logger instance.

## Modules and Navigation

### `module(string $slug): \Marwa\Module\ModuleHandle`

### `has_module(string $slug): bool`

```php
if (has_module('blog')) {
    $blog = module('blog');
}
```

Read the active module runtime from the application.

### `menu(): \Marwa\Framework\Navigation\MenuRegistry`

```php
$mainMenu = menu()->tree();
```

Returns the shared menu registry.

### `router(): mixed`

Returns the router adapter from the container.

## Debugging and Environment

### `debugger(): mixed`

Returns the debug bar instance when enabled, otherwise `null`.

### Environment Helpers

| Function | Description |
|----------|-------------|
| `is_local(): bool` | Check if running locally |
| `is_production(): bool` | Check if running in production |
| `running_in_console(): bool` | Check if running in CLI |

## Utility Helpers

### `generate_key(int $length = 32, bool $asHex = true): string`

Generates cryptographically secure random bytes or a hex-encoded key.

```php
$key = generate_key(); // 64-char hex string
$bytes = generate_key(16, false); // 16 raw bytes
```

### `with(mixed $value, callable $callback): mixed`

```php
$result = with($value, fn($v) => strtoupper($v));
```

Returns the value after passing it through a callback.

### `tap(mixed $value, callable $callback): mixed`

```php
tap($user, fn($u) => $u->save());
```

Executes a callback for side effects while returning the original value.

### `dd(mixed ...$vars): never`

```php
dd($variable);
```

Dumps values and terminates execution. Use only for local debugging.

## Backward Compatibility

All helper functions are available through the main `Helpers.php` file which re-exports all modular helper files. Existing code continues to work without any changes.
