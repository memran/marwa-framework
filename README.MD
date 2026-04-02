# Marwa Framework

[![Latest Version](https://img.shields.io/packagist/v/memran/marwa-framework.svg?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![Total Downloads](https://img.shields.io/packagist/dt/memran/marwa-framework.svg?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![CI](https://github.com/memran/marwa-framework/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-framework/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/memran/marwa-framework.svg?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![PHP Version](https://img.shields.io/packagist/php-v/memran/marwa-framework.svg?style=flat-square)](https://php.net)
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
- Console kernel integration via Symfony Console
- Twig-powered view adapter with theme support and cache directory bootstrapping
- Debug and maintenance middleware for local development and operational controls
- Production-ready quality tooling with PHPUnit, PHPStan, PHP-CS-Fixer, and GitHub Actions CI

## Documentation

- [Repository Guidelines](AGENTS.md)
- [Documentation Index](docs/README.md)
- [Quick Start Tutorial](docs/tutorials/quick-start.md)
- [HTTP Kernel Tutorial](docs/tutorials/http-kernel.md)
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

The repository ships sample routes in `routes/web.php` and demo templates in `resources/views/`. The framework will boot without consumer config files; if a host app provides `config/app.php`, `config/event.php`, or `config/view.php`, those settings override the built-in defaults.

## Project Layout

```text
src/                  Framework source
src/Adapters/         Integration adapters for router, events, view, error handling
src/Middlewares/      HTTP middleware
src/Providers/        Service providers
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

Use `APP_DEBUG=false` and `DEBUGBAR_ENABLED=false` in production. The framework now generates cryptographically secure request IDs and avoids loading missing config files as hard failures.

## Usage Example

```php
use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/health', fn () => Response::json(['ok' => true]))->register();
```

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
