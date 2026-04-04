# Marwa Framework

[![Packagist](https://img.shields.io/badge/Packagist-memran%2Fmarwa--framework-F28D1A?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/memran/marwa-framework)
[![Latest Version](https://img.shields.io/packagist/v/memran/marwa-framework?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/memran/marwa-framework)
[![Total Downloads](https://img.shields.io/packagist/dt/memran/marwa-framework?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![CI](https://github.com/memran/marwa-framework/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-framework/actions/workflows/ci.yml)
[![License](https://img.shields.io/github/license/memran/marwa-framework?style=flat-square)](https://github.com/memran/marwa-framework/blob/main/LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%206-brightgreen?style=flat-square)](https://phpstan.org/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-11.5-blue?style=flat-square)](https://phpunit.de/)

Marwa Framework is a lightweight, PSR-aligned PHP 8.2+ framework core for modular web apps, console tools, and reusable runtime services.

## What It Includes

- HTTP kernel, middleware pipeline, router integration, and lifecycle events
- Twig-based views with themes, modules, and base controller helpers
- Console application, scaffolding commands, scheduler, queue, and bootstrap cache
- Sessions, validation, security, cache, storage, mail, notifications, HTTP client, and error handling
- `marwa-db` and `marwa-module` integration for app-level runtime features

## Install

```bash
composer require memran/marwa-framework
```

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
php bin/console make:controller Admin/PostController --resource
php bin/console make:seeder UserSeeder
php bin/console make:model Billing/Invoice --migration
php bin/console make:module Blog
php bin/console make:theme dark --parent=default
php bin/console shell
php bin/console db:seed --class=DatabaseSeeder
php bin/console schedule:run --for=60 --sleep=1
```

## Documentation

- [Docs Index](docs/README.md)
- [Quick Start](docs/tutorials/quick-start.md)
- [Controllers](docs/tutorials/controllers.md)
- [Validation](docs/tutorials/validation.md)
- [View](docs/tutorials/view.md)
- [Console](docs/tutorials/console.md)
- [Seeding](docs/tutorials/seeding.md)
- [Architecture](docs/architecture.md)

## Notes

- Generated controllers extend `Marwa\Framework\Controllers\Controller`.
- Validation failures flash `_old_input` and `errors` into the session.
- Theme manifests live in `resources/views/themes/<theme>/manifest.php` by default.
- `APP_KEY` is required for encrypted sessions.

## Development

```bash
composer test
composer analyse
composer lint
```
