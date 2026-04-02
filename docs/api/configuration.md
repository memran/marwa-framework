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
        App\Events\UserRegistered::class => [
            [App\Listeners\SendWelcomeEmail::class, 'handle'],
        ],
    ],
    'subscribers' => [
        App\Listeners\UserSubscriber::class,
    ],
];
```

## `config/logger.php`

Defined by `Marwa\Framework\Config\LoggerConfig`.

Supported keys:

- `enable`: boolean logging toggle
- `filter`: list of sensitive keys to redact
- `storage.driver`: sink driver such as `file`
- `storage.path`: log output directory
- `storage.prefix`: file name prefix
