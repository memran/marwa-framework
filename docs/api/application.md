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
$menu = $app->make(\Marwa\Framework\Navigation\MenuRegistry::class);
```

Short description:
Resolves a dependency from the container, registering it lazily when needed. This is also how you access shared framework services such as the menu registry.

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

### `registerCommand(object|string $command): void`

```php
$app->registerCommand(App\Console\Commands\CleanupCommand::class);
```

Short description:
Registers a Symfony Console command for the shared console kernel.

### `mailer(): MailerInterface`

```php
$mailer = $app->mailer();
```

Short description:
Returns the shared SwiftMailer-compatible mail service.

### `http(): HttpClientInterface`

```php
$http = $app->http();
```

Short description:
Returns the shared Guzzle-backed HTTP client wrapper.

### `notifications(): NotificationManager`

```php
$notifications = $app->notifications();
```

Short description:
Returns the shared notification manager.

### `console(): ConsoleKernel`

```php
$exitCode = $app->console()->handle();
```

Short description:
Returns the shared Symfony Console runtime wrapper.

### `modules(): array`

```php
$modules = $app->modules();
```

Short description:
Returns the loaded `marwa-module` registry contents keyed by module slug.

### `hasModule(string $slug): bool`

```php
if ($app->hasModule('blog')) {
    // ...
}
```

Short description:
Checks whether a module has been discovered and bootstrapped.

### `module(string $slug): ModuleHandle`

```php
$blog = $app->module('blog');
$views = $blog->path('views');
```

Short description:
Returns the typed module handle for a loaded module.

## Related Runtime Services

### `Marwa\Framework\Navigation\MenuRegistry`

Resolve the shared menu registry from the application container:

```php
$menu = $app->make(\Marwa\Framework\Navigation\MenuRegistry::class);

$menu->add([
    'name' => 'dashboard',
    'label' => 'Dashboard',
    'url' => '/dashboard',
    'order' => 10,
    'visible' => static fn (): bool => user()?->hasPermission('dashboard.view') === true,
]);

$tree = $menu->tree();
```

Short description:
Collects normalized menu items for the main application navigation. Module providers can register menu items during boot and views can consume the final `mainMenu` tree.

Menu visibility is presentation-only. Always enforce backend access with controller guards, policies, or route middleware.
