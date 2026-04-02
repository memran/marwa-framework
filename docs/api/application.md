# Application API

## `Marwa\Framework\Application`

The `Application` class is the root bootstrap object for the framework.

### Constructor

```php
$app = new Application($basePath);
```

Short description:
Creates the service container, loads `.env`, binds core singletons, and stores the base path.

### `boot(): void`

```php
$app->boot();
```

Short description:
Dispatches the application boot event once.

### `make(string $abstract): mixed`

```php
$logger = $app->make(\Marwa\Framework\Adapters\Logger\LoggerAdapter::class);
```

Short description:
Resolves a dependency from the container, registering it lazily when needed.

### `singleton(string $abstract): mixed`

Short description:
Registers and resolves a shared service.

### `basePath(string $path = ''): string`

```php
$configDir = $app->basePath('config');
```

Short description:
Returns the application base path or a path relative to it.

### `environment(?string $env = null): string|bool|null`

```php
$current = $app->environment();
$isProd = $app->environment('production');
```

Short description:
Returns the current environment name, or checks whether it matches the provided value.
