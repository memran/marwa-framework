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
php bin/console route:cache
php bin/console module:cache
php bin/console make:command CleanupCommand
php bin/console make:controller Admin/PostController --resource
php bin/console make:model Billing/Invoice --migration
php bin/console make:ai-helper SupportAgent --with-command
```

`make:controller` generates controllers in `app/Http/Controllers`. Use `--resource` to scaffold CRUD-style methods.

`make:model` generates `marwa-db` model classes in `app/Models`. Use `--migration` to call the registered `make:migration` command and create a matching table migration in `database/migrations`.
