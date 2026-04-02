# Console Flow

## Overview

Marwa Framework uses Symfony Console for CLI tooling. The console layer shares the same application container as HTTP, so commands can resolve services, config, logging, and providers without extra boot code.

## Boot Sequence

1. `Application` boots the environment and container.
2. `ConsoleKernel` loads `config/app.php` and `config/console.php`.
3. `AppBootstrapper` registers application providers and module runtime services.
4. Commands are collected from built-in framework commands, `config/console.php`, programmatic registration, discovery rules, module command paths, and enabled package integrations like `marwa-db`.
5. `ConsoleApplication` starts Symfony Console.

## Minimal Usage

```php
use Marwa\Framework\Application;

$app = new Application(dirname(__DIR__));
$app->registerCommand(App\Console\Commands\CleanupCommand::class);

exit($app->console()->handle());
```

## Discovery

```php
<?php

return [
    'discover' => [
        ['namespace' => 'App\\Console\\Commands', 'path' => 'app/Console/Commands'],
    ],
];
```

When `config/module.php` enables `marwa-module`, the console kernel also discovers commands from module directories declared through manifest `paths.commands` or the default `Console/Commands` and `src/Console/Commands` conventions.

When `config/database.php` enables `marwa-db`, the console kernel also registers:

- `migrate`
- `migrate:rollback`
- `migrate:refresh`
- `migrate:status`
- `make:migration`
- `make:seeder`
- `db:seed`

## Scaffolding

```bash
php bin/console bootstrap:cache
php bin/console bootstrap:clear
php bin/console config:cache
php bin/console key:generate --show-env
php bin/console route:cache
php bin/console module:cache
php bin/console make:command CleanupCommand
php bin/console make:controller Admin/PostController --resource
php bin/console make:model Billing/Invoice --migration
php bin/console make:module Blog
php bin/console make:theme dark --parent=default
php bin/console make:ai-helper SupportAgent --with-command
```

`make:controller` generates controllers in `app/Http/Controllers`. Use `--resource` to scaffold CRUD-style methods.

`make:model` generates `marwa-db` model classes in `app/Models`. Use `--migration` to call the registered `make:migration` command and create a matching table migration in `database/migrations`.

`make:module` generates a `marwa-module` compatible folder in the first configured `module.paths` location, including `manifest.php`, a module service provider, `routes/http.php`, `resources/views/index.twig`, and `Console/Commands`.

For the generated provider to autoload in a host application, map `App\\Modules\\` to `modules/` in the consumer `composer.json`.

`make:theme` generates `resources/views/themes/<name>` with a valid `marwa-view` `manifest.php`, starter Twig templates, and an `assets/css/app.css` file. Use `--parent` to scaffold theme inheritance.

`key:generate` prints a cryptographically secure random key using the shared helper implementation. Use `--show-env` for `APP_KEY=...` output, `--length` to control byte length, and `--raw` if you do not want hex encoding.

## Scheduling

The framework includes a lightweight scheduler and a file-backed queue for deferred jobs.

```php
use Marwa\Framework\Application;

$app = new Application(dirname(__DIR__));

$app->schedule()
    ->call(function (Application $app, DateTimeImmutable $time): void {
        file_put_contents($app->basePath('storage/heartbeat.log'), $time->format(DATE_ATOM) . PHP_EOL, FILE_APPEND);
    }, 'heartbeat')
    ->everySecond();

$app->schedule()
    ->queue('reports:send', ['type' => 'daily'], name: 'queue-report')
    ->everyMinute();
```

Run the scheduler once:

```bash
php bin/console schedule:run
```

Run it every second from cron by keeping the process alive for one minute:

```cron
* * * * * php /path/to/bin/console schedule:run --for=60 --sleep=1 >> /dev/null 2>&1
```

Queued jobs are written to `storage/queue/<queue>/pending`, move to `processing` while reserved, and land in `failed` when explicitly failed by user code.
