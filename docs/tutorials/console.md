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
php bin/console make:mail WelcomeMail
php bin/console make:seeder UserSeeder
php bin/console make:model Billing/Invoice --migration
php bin/console make:module Blog
php bin/console make:theme dark --parent=default
php bin/console make:ai-helper SupportAgent --with-command
php bin/console shell
php bin/console schedule:table
```

`make:controller` generates controllers in `app/Http/Controllers` and extends `Marwa\Framework\Controllers\Controller`. Use `--resource` to scaffold CRUD-style methods.

`make:mail` generates `App\Mail\...` classes that extend the framework `Mailable` base and show how to build a SwiftMailer-compatible message.

`make:model` generates `marwa-db` model classes in `app/Models`. Use `--migration` to call the registered `make:migration` command and create a matching table migration in `database/migrations`.

`make:seeder` generates a Faker-ready seeder in `database/seeders` that extends `Marwa\Framework\Database\Seeder`. Use it to bulk insert fake data with `faker()`, `insertMany()`, and `truncate()`.

`make:module` generates a `marwa-module` compatible folder in the first configured `module.paths` location, including `manifest.php`, a module service provider, `routes/http.php`, `resources/views/index.twig`, and `Console/Commands`.

For the generated provider to autoload in a host application, map `App\\Modules\\` to `modules/` in the consumer `composer.json`.

`make:theme` generates `resources/views/themes/<name>` with a valid `marwa-view` `manifest.php`, starter Twig templates, and an `assets/css/app.css` file. Use `--parent` to scaffold theme inheritance.

`shell` opens an interactive PsySH session when the optional `psy/psysh` package is installed. It exposes `$app`, `$container`, `$config`, and `$logger` for live debugging. If PsySH is not installed, the command prints install instructions and exits with a failure code.

`key:generate` prints a cryptographically secure random key using the shared helper implementation. Use `--show-env` for `APP_KEY=...` output, `--length` to control byte length, and `--raw` if you do not want hex encoding.

`db:seed` runs discovered seeders from the configured database seeder path. Pass `--class=DatabaseSeeder` to run a single entry point, `--dry-run` to inspect discovery, or `--no-transaction` if you want to manage transactions yourself.

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

Scheduler storage is configurable through `config/schedule.php`:

```php
return [
    'driver' => 'file', // or cache, database
    'file' => [
        'path' => storage_path('framework/schedule'),
    ],
    'cache' => [
        'namespace' => 'schedule',
    ],
    'database' => [
        'connection' => 'sqlite',
        'table' => 'schedule_jobs',
    ],
];
```

If you use the `database` driver, generate the table migration first:

```bash
php bin/console schedule:table
php bin/console migrate
```

With the file driver, state and overlap locks are stored below `storage/framework/schedule`. With the cache driver, the scheduler uses the configured cache backend and key namespace for both state and overlap locks. With the database driver, the scheduler persists task status and overlap locks in the configured `schedule_jobs` table.

Queued jobs are written to `storage/queue/<queue>/pending`, move to `processing` while reserved, and land in `failed` when explicitly failed by user code.
