# Marwa Framework

[![Packagist](https://img.shields.io/badge/Packagist-memran%2Fmarwa--framework-F28D1A?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/memran/marwa-framework)
[![Latest Version](https://img.shields.io/packagist/v/memran/marwa-framework?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/memran/marwa-framework)
[![Total Downloads](https://img.shields.io/packagist/dt/memran/marwa-framework?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![CI](https://github.com/memran/marwa-framework/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-framework/actions/workflows/ci.yml)
[![License](https://img.shields.io/github/license/memran/marwa-framework?style=flat-square)](https://github.com/memran/marwa-framework/blob/main/LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%206-brightgreen?style=flat-square)](https://phpstan.org/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-11.5-blue?style=flat-square)](https://phpunit.de/)

Marwa Framework is a modular, PSR-aligned PHP 8.2+ framework core for applications that want a lightweight bootstrap layer without giving up routing, middleware, events, console support, or modern developer ergonomics. It is designed as a library-first foundation that stays simple to extend in host applications.

## Package

- Package name: `memran/marwa-framework`
- Package type: Composer library
- Install command: `composer require memran/marwa-framework`

## Features

- PSR-4 autoloading and PSR-friendly HTTP, container, logging, cache, and event integration
- HTTP kernel with middleware pipeline, route dispatching, and router-backed not-found handling
- Environment and configuration bootstrapping with safe fallback behavior when consumer config files are missing
- Secure encrypted session support with hardened cookie defaults and lazy HTTP session startup
- Path-bound file utility for atomic writes, JSON serialization, copy, move, and deletion workflows
- GD-backed image utility for loading, resizing, cropping, fitting, and saving common web image formats
- Scrapbook-backed cache service with SQLite or memory drivers, buffering, collections, and optional stampede protection
- Symfony Console integration with a shared application container, abstract command base class, command discovery, and scaffolding commands
- Built-in scaffolding for commands, controllers, and `marwa-db` models with optional migration generation
- Lightweight scheduler and file-backed queue services for cron-driven recurring work and deferred jobs
- First-class `marwa-module` runtime integration with module providers, routes, views, registry bindings, and module command discovery
- First-class `marwa-db` integration with shared connection bootstrap and migration/seeder console commands
- Twig-powered view adapter with theme support and cache directory bootstrapping
- Shared error handling through `marwa-error-handler` for HTTP and console with logger/debug reporter integration
- Debug and maintenance middleware for local development and operational controls
- Production-ready quality tooling with PHPUnit, PHPStan, PHP-CS-Fixer, and GitHub Actions CI

## Documentation

- [Repository Guidelines](AGENTS.md)
- [Documentation Index](docs/README.md)
- [Quick Start Tutorial](docs/tutorials/quick-start.md)
- [HTTP Kernel Tutorial](docs/tutorials/http-kernel.md)
- [Events Tutorial](docs/tutorials/events.md)
- [Console Tutorial](docs/tutorials/console.md)
- [Architecture](docs/architecture.md)
- [API Reference](docs/api/application.md)

## Requirements

- PHP 8.2 or newer
- Composer
- Extensions required by installed dependencies

## Installation

```bash
composer require memran/marwa-framework
```

For repository development:

```bash
git clone https://github.com/memran/marwa-framework.git
cd marwa-framework
composer install
cp .env.example .env
```

The repository ships sample routes in `routes/web.php` and demo templates in `resources/views/`. The framework will boot without consumer config files; if a host app provides `config/app.php`, `config/event.php`, `config/view.php`, `config/error.php`, or `config/database.php`, those settings override the built-in defaults.

Encrypted sessions require `APP_KEY` in `.env`. Generate one with:

```bash
php bin/console key:generate --show-env
```

## Ecosystem Alignment

The framework is aligned around the MarwaPHP package set:

- `memran/marwa-router` for routing and not-found handling
- `memran/marwa-view` for Twig-based rendering and theming
- `memran/marwa-event` for PSR-14 event dispatching with listener priorities
- `memran/marwa-module` for runtime module discovery, provider boot, routes, views, and console command discovery
- `memran/marwa-db` for shared database bootstrap, ORM/schema wiring, and migration/seeder console commands
- `memran/marwa-error-handler`, `memran/marwa-debugbar`, and `memran/marwa-logger` for runtime diagnostics
- optional `memran/marwa-module`, `memran/marwa-entity`, and `memran/marwa-support` integrations in consuming apps

`marwa-db` console commands are auto-registered when that package is installed and exposes a compatible configurator or command namespace.

## Project Layout

```text
src/                  Framework source
src/Console/          Console kernel, commands, and discovery services
src/Adapters/         Integration adapters for router, events, view, error handling
src/Middlewares/      HTTP middleware
src/Providers/        Service providers
src/Stubs/            Framework command and AI helper scaffolds
routes/               Example route registration
resources/views/      Example templates
tests/                PHPUnit coverage for framework behavior
```

## Development Commands

```bash
composer test          # Run PHPUnit
composer test:coverage # Run PHPUnit with text coverage output
composer analyse       # Run PHPStan
composer lint          # Check code style with PHP-CS-Fixer
composer fix           # Apply code style fixes
composer ci            # Run analyse + lint + tests
```

Useful scaffolding commands:

```bash
php bin/console make:command CleanupCommand
php bin/console key:generate --show-env
php bin/console make:controller Admin/PostController --resource
php bin/console make:model Billing/Invoice --migration
php bin/console make:module Blog
php bin/console make:theme dark --parent=default
php bin/console schedule:run --for=60 --sleep=1
```

## PHPUnit

PHPUnit is used for framework behavior and regression testing.

```bash
composer test
composer test:coverage
```

The current repository test suite lives in `tests/` and covers configuration loading, application environment behavior, event lifecycle defaults, and request ID middleware behavior.

## PHPStan

PHPStan is configured at level 6 in `phpstan.neon` and runs against `src/` and `tests/`.

```bash
composer analyse
```

Use PHPStan before opening a pull request to catch contract mismatches, invalid docblocks, missing iterable types, and facade/static API drift.

## Configuration

Environment variables are loaded from `.env`. Important defaults include:

```dotenv
APP_ENV=development
APP_DEBUG=true
DEBUGBAR_ENABLED=false
MAINTENANCE=0
TIMEZONE=Asia/Dhaka
```

Use `APP_DEBUG=false` and `DEBUGBAR_ENABLED=false` in production. The framework now generates cryptographically secure request IDs, avoids loading missing config files as hard failures, and registers shared PHP error, exception, and shutdown handlers for both HTTP and console runtimes.

## Sessions

The framework provides encrypted session storage through `Marwa\Framework\Contracts\SessionInterface` and the `session()` helper.

```php
session()->set('user_id', 42);
session()->flash('status', 'Profile updated');
$userId = session('user_id');
session()->regenerate();
```

Optional `config/session.php`:

```php
<?php

return [
    'enabled' => true,
    'autoStart' => false,
    'name' => 'marwa_session',
    'secure' => env('APP_ENV') === 'production',
    'httpOnly' => true,
    'sameSite' => 'Lax',
    'encrypt' => true,
];
```

## Cache

The framework exposes a Scrapbook-backed cache service through the container, helper, and facade.

```php
cache()->put('settings.theme', 'light', 300);
$theme = cache('settings.theme', 'dark');

cache()->remember('stats', 60, function (): array {
    return ['users' => 120];
});
```

By default the cache uses SQLite at `storage/cache/framework.sqlite` when `pdo_sqlite` is available, and falls back to an in-memory store otherwise.

## File Support

Use `Marwa\Framework\Supports\File` for atomic writes and common filesystem operations.

```php
use Marwa\Framework\Supports\File;

$config = File::path(storage_path('cache/app.json'))
    ->writeJson(['ready' => true])
    ->readJson();
```

## Usage Example

```php
use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/health', fn () => Response::json(['ok' => true]))->register();
```

## Events

Framework and application events are configured in `config/event.php`.

```php
<?php

use App\Listeners\LogRequestHandled;
use Marwa\Framework\Adapters\Event\RequestHandled;

return [
    'listeners' => [
        RequestHandled::class => [
            [LogRequestHandled::class, 'handle'],
        ],
    ],
    'subscribers' => [],
];
```

Lifecycle events include `ApplicationStarted`, `AppBooted`, `RequestHandlingStarted`, `RequestHandled`, `AppTerminated`, and `ConsoleBootstrapped`. See [docs/tutorials/events.md](/Users/memran/projects/php-projects/marwa-framework/docs/tutorials/events.md) for full examples.

## Database

Enable database integration in `config/database.php`:

```php
<?php

return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => base_path('database/database.sqlite'),
        ],
    ],
];
```

When enabled, the framework bootstraps `Marwa\DB\Connection\ConnectionManager`, initializes `Marwa\DB\Facades\DB`, `Marwa\DB\ORM\Model`, and `Marwa\DB\Schema\Schema`, and registers these console commands:

- `migrate`
- `migrate:rollback`
- `migrate:refresh`
- `migrate:status`
- `make:migration`
- `make:seeder`
- `db:seed`

## Scheduling and Queue

Register recurring tasks through the shared application scheduler:

```php
use Marwa\Framework\Application;

$app = new Application(dirname(__DIR__));

$app->schedule()
    ->call(function (Application $app, DateTimeImmutable $time): void {
        file_put_contents($app->basePath('storage/heartbeat.log'), $time->format(DATE_ATOM) . PHP_EOL, FILE_APPEND);
    }, 'heartbeat')
    ->everySecond();

$app->schedule()
    ->queue('emails:send-digest', ['batch' => 1], name: 'queue-digest')
    ->everyMinute();
```

Run due tasks every second from cron by keeping the command alive for one minute:

```cron
* * * * * php /path/to/bin/console schedule:run --for=60 --sleep=1 >> /dev/null 2>&1
```

The file-backed queue stores pending, processing, and failed jobs under `storage/queue`. Override defaults through `config/queue.php` and `config/schedule.php`.

## Lifecycle

### HTTP Lifecycle

```text
Application
  -> boot container + env + core singletons
  -> HttpKernel
      -> load app config
      -> register service providers
      -> bootstrap modules
      -> build middleware pipeline
      -> dispatch AppBooted event
      -> Relay pipeline
          -> RequestIdMiddleware
          -> MaintenanceMiddleware
          -> RouterMiddleware
          -> DebugbarMiddleware
      -> router dispatch
      -> PSR-7 response
      -> dispatch AppTerminated event
      -> emit response
```

### Console Lifecycle

```text
Application
  -> boot container + env + core singletons
  -> ConsoleKernel
      -> load app + console config
      -> register service providers
      -> bootstrap modules
      -> collect built-in commands
      -> collect configured commands
      -> discover package/app/module commands
      -> build Symfony Console application
      -> run selected command
```

### Runtime Split

```text
Application
  -> shared container, config, logger, events
  -> HttpKernel for web requests
  -> ConsoleKernel for CLI commands
```

## Console

The console kernel uses Symfony Console and the same DI container as the HTTP kernel.

```php
use Marwa\Framework\Application;

$app = new Application(__DIR__);
$app->registerCommand(App\Console\Commands\CleanupCommand::class);

exit($app->console()->handle());
```

Optional `config/console.php`:

```php
<?php

return [
    'name' => 'My App Console',
    'version' => '1.0.0',
    'commands' => [
        App\Console\Commands\CleanupCommand::class,
    ],
    'discover' => [
        ['namespace' => 'App\\Console\\Commands', 'path' => 'app/Console/Commands'],
    ],
];
```

Optional `config/module.php`:

```php
<?php

return [
    'enabled' => true,
    'paths' => [
        base_path('modules'),
    ],
    'commandPaths' => ['commands'],
    'commandConventions' => ['Console/Commands', 'src/Console/Commands'],
];
```

When `marwa-module` is enabled, the runtime binds `ModuleRepositoryInterface`, `ModuleRegistryInterface`, and `ModuleBuilder`, boots module service providers, loads module routes and view namespaces, and auto-discovers Symfony commands from module command directories.

Scaffolding commands:

```bash
php bin/console bootstrap:cache
php bin/console bootstrap:clear
php bin/console config:cache
php bin/console route:cache
php bin/console module:cache
php bin/console make:command CleanupCommand
php bin/console make:ai-helper SupportAgent --with-command
```

## Bootstrap Cache

The framework now includes bootstrap cache infrastructure for starter distributions such as `marwa-php`.

Cached artifacts:

- config cache: `bootstrap/cache/config.php`
- route cache: `bootstrap/cache/routes.php`
- module cache: `bootstrap/cache/modules.php`

Supported commands:

```bash
php bin/console bootstrap:cache
php bin/console bootstrap:clear
php bin/console config:cache
php bin/console config:clear
php bin/console route:cache
php bin/console route:clear
php bin/console module:cache
php bin/console module:clear
```

Current behavior:

- `AppBootstrapper` loads cached config when `bootstrap/cache/config.php` exists
- `KernalServiceProvider` loads compiled routes when `bootstrap/cache/routes.php` exists
- `ModuleBootstrapper` loads module manifests from `config/module.php` or code defaults and reuses the module cache when present

## Quality and CI

GitHub Actions runs Composer validation, static analysis, linting, and PHPUnit on every pull request and push to `main`. Keep changes small, add tests for behavior changes, and prefer focused commits with imperative subjects such as `Fix middleware registration`.

## Deployment Notes

- Install with `composer install --no-dev --prefer-dist --optimize-autoloader`
- Keep `.env` out of version control
- Disable debug output in production
- Review route and view files in the consuming app before deployment

## Contributing

See [AGENTS.md](AGENTS.md) for repository-specific contributor guidance. Pull requests should describe the problem, the approach taken, and the verification steps used.

## Community

- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Contributing Guide](CONTRIBUTING.MD)
- [Security Policy](SECURITY.md)
- [Support](SUPPORT.md)

## License

Licensed under the [MIT License](LICENSE).
