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
