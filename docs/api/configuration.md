# Configuration Contracts

The framework supports optional consumer config files in `config/`. Missing files are ignored and replaced by code-level defaults defined in `src/Config/`.

## `config/app.php`

Defined by `Marwa\Framework\Config\AppConfig`.

Supported keys:

- `providers`: list of service provider class names
- `middlewares`: list of middleware class names
- `debugbar`: boolean flag for debug bar registration
- `collectors`: list of debug bar collector class names

Example:

```php
return [
    'providers' => [
        App\Providers\AppServiceProvider::class,
    ],
    'middlewares' => [
        App\Http\Middleware\TrustProxies::class,
    ],
    'debugbar' => false,
    'collectors' => [],
];
```

## `config/view.php`

Defined by `Marwa\Framework\Config\ViewConfig`.

Supported keys:

- `viewsPath`: absolute or base-path-resolved views directory
- `cachePath`: writable compiled-template cache directory
- `debug`: enable template debug behavior
- `defaultTheme`: default theme name

## `config/event.php`

Defined by `Marwa\Framework\Config\EventConfig`.

Supported keys:

- `listeners`: event-to-listener map
- `subscribers`: list of subscriber classes

Example:

```php
return [
    'listeners' => [
        Marwa\Framework\Adapters\Event\AppBooted::class => [
            [App\Listeners\LogApplicationBoot::class, 'handle'],
        ],
        Marwa\Framework\Adapters\Event\RequestHandled::class => [
            [App\Listeners\LogRequestHandled::class, 'handle'],
        ],
    ],
    'subscribers' => [
        App\Listeners\RuntimeSubscriber::class,
    ],
];
```

Lifecycle events available out of the box:

- `ApplicationStarted`
- `ApplicationBootstrapping`
- `ProvidersBootstrapped`
- `ErrorHandlerBootstrapped`
- `ModulesBootstrapped`
- `AppBooted`
- `RequestHandlingStarted`
- `RequestHandled`
- `AppTerminated`
- `ConsoleBootstrapped`

## `config/logger.php`

Defined by `Marwa\Framework\Config\LoggerConfig`.

Supported keys:

- `enable`: boolean logging toggle
- `filter`: list of sensitive keys to redact
- `storage.driver`: sink driver such as `file`
- `storage.path`: log output directory
- `storage.prefix`: file name prefix

## `config/error.php`

Defined by `Marwa\Framework\Config\ErrorConfig`.

Supported keys:

- `enabled`: register the global PHP error, exception, and shutdown handlers
- `appName`: application name shown in fallback output and logs
- `environment`: environment passed to `marwa-error-handler`
- `useLogger`: forward uncaught errors to the shared PSR-3 logger
- `useDebugReporter`: report uncaught exceptions to the debug bar when available
- `renderer`: custom `Marwa\ErrorHandler\Contracts\RendererInterface` implementation

Example:

```php
return [
    'enabled' => true,
    'appName' => 'My App',
    'environment' => env('APP_ENV', 'production'),
    'useLogger' => true,
    'useDebugReporter' => true,
    'renderer' => Marwa\ErrorHandler\Support\FallbackRenderer::class,
];
```

## `config/database.php`

Defined by `Marwa\Framework\Config\DatabaseConfig`.

Supported keys:

- `enabled`: enable shared `marwa-db` bootstrap
- `default`: default connection name
- `connections`: named connection map in the `marwa-db` package format
- `debug`: default debug flag applied to connections unless overridden
- `useDebugPanel`: enable the package debug panel on the connection manager
- `migrationsPath`: application migrations directory
- `seedersPath`: application seeders directory
- `seedersNamespace`: namespace used by seeder discovery and scaffolding

Example:

```php
return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => base_path('database/database.sqlite'),
            'debug' => false,
        ],
    ],
    'migrationsPath' => base_path('database/migrations'),
    'seedersPath' => base_path('database/seeders'),
    'seedersNamespace' => 'Database\\Seeders',
];
```

## `config/bootstrap.php`

Defined by `Marwa\Framework\Config\BootstrapConfig`.

Supported keys:

- `configCache`: full path to cached merged config output
- `routeCache`: full path to compiled route cache output
- `moduleCache`: full path to module manifest cache output

Default paths:

- `bootstrap/cache/config.php`
- `bootstrap/cache/routes.php`
- `bootstrap/cache/modules.php`

## `config/module.php`

Defined by `Marwa\Framework\Config\ModuleConfig`.

Supported keys:

- `enabled`: enable module-aware starter behavior
- `paths`: list of module root directories
- `cache`: full path to the module manifest cache file
- `forceRefresh`: ignore the module cache and rescan the filesystem
- `commandPaths`: manifest `paths` keys that should be treated as command directories
- `commandConventions`: module-relative fallback directories for command discovery

Example:

```php
return [
    'enabled' => true,
    'paths' => [
        base_path('modules'),
    ],
    'cache' => base_path('bootstrap/cache/modules.php'),
    'forceRefresh' => false,
    'commandPaths' => ['commands'],
    'commandConventions' => ['Console/Commands', 'src/Console/Commands'],
];
```
