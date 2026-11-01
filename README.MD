# Marwa Framework

[![Packagist](https://img.shields.io/badge/Packagist-memran%2Fmarwa--framework-F28D1A?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/memran/marwa-framework)
[![Latest Version](https://img.shields.io/packagist/v/memran/marwa-framework?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/memran/marwa-framework)
[![Total Downloads](https://img.shields.io/packagist/dt/memran/marwa-framework?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![CI](https://github.com/memran/marwa-framework/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-framework/actions/workflows/ci.yml)
[![License](https://img.shields.io/github/license/memran/marwa-framework?style=flat-square)](https://github.com/memran/marwa-framework/blob/main/LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%206-brightgreen?style=flat-square)](https://phpstan.org/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-11.5-blue?style=flat-square)](https://phpunit.de/)

## Documentation

📖 Full documentation at: **[https://memran.github.io/marwa-framework/](https://memran.github.io/marwa-framework/)**

Marwa Framework is a lightweight, PSR-aligned PHP 8.2+ framework core for modular web apps, console tools, and reusable runtime services.

## What It Includes

- HTTP kernel, middleware pipeline, router integration, and lifecycle events
- Twig-based views with themes, modules, and base controller helpers
- Framework-level menu registry for app and module-driven navigation
- Console application, scaffolding commands, scheduler, queue, and bootstrap cache
- Sessions, validation, security, cache, storage, mail, notifications, Kafka publish/consume, HTTP client, and error handling
- `marwa-db` and `marwa-module` integration for app-level runtime features

## Install

```bash
composer require memran/marwa-framework
```

If you want a ready-to-run application skeleton instead of wiring the framework into a project manually, start with [`memran/marwa-php`](https://github.com/memran/marwa-php). It is the recommended starter application for this framework and shows the expected app structure, bootstrap files, routes, config, and developer workflow.

For local development:

```bash
git clone https://github.com/memran/marwa-framework.git
cd marwa-framework
composer install
cp .env.example .env
```

## Quick Start

```php
use Marwa\Framework\Application;

$app = new Application(__DIR__);
$app->boot();
$response = $app->http()->handle($request);
```

```bash
# Console Commands
php marwa make:controller Admin/PostController --resource
php marwa make:seeder UserSeeder
php marwa make:model Billing/Invoice --migration
php marwa make:module Blog
php marwa make:theme dark --parent=default
php marwa shell
php marwa security:report --since-hours=24 --prune-days=30
php marwa db:seed --class=DatabaseSeeder
php marwa schedule:run --for=60 --sleep=1

# Database Management (DBForge)
php marwa db:create myapp
php marwa db:drop myapp
php marwa db:list
php marwa db:list --tables
php marwa db:backup
php marwa db:restore backup.sql
php marwa db:optimize
php marwa db:analyze
```

## Documentation

- [Docs Index](docs/README.md)
- [Starter Application: memran/marwa-php](https://github.com/memran/marwa-php)
- [Quick Start](docs/tutorials/quick-start.md)
- [Controllers](docs/tutorials/controllers.md)
- [Validation](docs/tutorials/validation.md)
- [View](docs/tutorials/view.md)
- [Error Pages](docs/tutorials/error-pages.md)
- [Console](docs/tutorials/console.md)
- [Modules](docs/tutorials/modules.md)
- [Seeding](docs/tutorials/seeding.md)
- [DebugBar](docs/tutorials/debugbar.md)
- [Architecture](docs/architecture.md)

## Notes

- Generated controllers extend `Marwa\Framework\Controllers\Controller`.
- Validation failures flash `_old_input` and `errors` into the session.
- Theme manifests live in `resources/views/themes/<theme>/manifest.php` by default.
- `APP_KEY` is required for encrypted sessions.
- Modules can contribute navigation through `Marwa\Framework\Navigation\MenuRegistry` and the `menu()` helper.

## Development

```bash
composer test
composer analyse
composer lint
```

Run `composer lint` after manual PHP edits before opening a PR. PHP CS Fixer output is the source of truth for import order, whitespace, braces, and blank lines.

## Database queue reservations

Database jobs are claimed with a conditional update and a random reservation token.
`queue.reservationSeconds` defaults to 300; expired reservations can be reclaimed.
Acknowledgements are fenced by token so an old worker cannot complete a reclaimed job.
`queue.tries` (or worker `--tries`) also caps crash recovery attempts. Malformed payloads
are failed and skipped. Delivery remains at least once: handlers must be idempotent.

`queue:work --job-timeout=60 --memory-limit=128 --max-jobs=1000 --max-time=3600`
adds a PCNTL deadline and recycles at a memory limit between jobs. The job deadline
must be shorter than the reservation lease. `queue.jobTimeout` defaults to zero for
compatibility; positive deadlines require PCNTL and a process supervisor. The signal
handler throws an exception; code that suppresses signals or exceptions still requires
external process limits and bounded I/O. `--timeout` remains an alias for the worker
lifetime `--max-time`, not a per-job deadline.

## Owned background scheduler execution

Database scheduling now requires a companion `<schedule_table>_locks` table with
primary-key `name`, string(32) `token`, nullable integer `last_dispatched_at` and
`expires_at` datetime. The schedule-table generator creates both tables for new
installs; existing installations need an additive migration and a coordinated
scheduler/worker restart. Reservations use conditional claims, token-fenced state
updates and release, and keep the last automatic occurrence after completion.

Mark registered tasks `inBackground()` to reserve and enqueue a native
`ScheduledTaskJob`, or use `Scheduler::dispatch($registeredName)` for a manual run.
It requires the database scheduler store and positive `queue.jobTimeout` shorter
than `schedule.backgroundLeaseSeconds` (default 900). Expired/disabled/missing tasks
fail without executing the callback. Business exceptions are non-retryable wrapper
failures; ordinary queue jobs retain their normal retries. Handlers must still be
idempotent for process-crash redelivery. Scheduler summaries include a `queued` list,
and CLI output distinguishes queued tasks and returns failure for dispatch errors.
