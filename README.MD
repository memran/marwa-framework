# Marwa Framework

[![CI](https://github.com/memran/marwa-framework/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-framework/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/memran/marwa-framework.svg?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![PHP Version](https://img.shields.io/packagist/php-v/memran/marwa-framework.svg?style=flat-square)](https://php.net)

Marwa Framework is a modular PHP 8.2+ framework core focused on HTTP bootstrapping, routing, middleware orchestration, events, and console integration. The codebase targets library consumers rather than a full standalone app skeleton.

## Requirements

- PHP 8.2 or newer
- Composer
- Extensions required by installed dependencies

## Quick Start

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

## License

Licensed under the [MIT License](LICENSE).
