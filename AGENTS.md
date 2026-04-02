# Repository Guidelines

## Project Structure & Module Organization
This repository is a Composer package for the `Marwa\\Framework\\` namespace. Core code lives in `src/`, organized by responsibility: `Adapters/`, `Contracts/`, `Facades/`, `Middlewares/`, `Providers/`, `Console/`, `Exceptions/`, and `Supports/`. Route definitions are in `routes/web.php`, and Twig view templates live in `resources/views/`. The executable CLI entrypoint is `marwa`. A `tests/` directory is referenced by autoload and analysis config but is not present yet; add new test files there.

## Build, Test, and Development Commands
Install dependencies with `composer install`. Run the test suite with `composer test`, which executes `php vendor/bin/testify` using `phpunit.config.php`. Run static analysis with `composer stan` to analyze `src/` and `tests/` at PHPStan level 6. For local manual checks, common commands are `php marwa` for the CLI and `php -S localhost:8000 -t public` when validating framework integration in a host app.

## Coding Style & Naming Conventions
Follow the existing PHP style in `src/`: `declare(strict_types=1);`, PSR-4 namespaces, typed properties, and explicit return types. Use 4-space indentation. Keep class names PascalCase, interfaces suffixed with `Interface`, exceptions suffixed with `Exception`, and service providers suffixed with `ServiceProvider`. Prefer small, single-purpose classes in the matching domain folder, for example `src/Middlewares/RequestIdMiddleware.php`.

## Testing Guidelines
Place tests in `tests/` and use either `*Test.php` or `*_test.php`, matching `phpunit.config.php`. Add tests alongside any behavior change in routing, bootstrapping, middleware, or adapters. Run `composer test` before opening a PR, then run `composer stan` to catch type and contract regressions.

## Commit & Pull Request Guidelines
Recent commits use short, imperative subjects such as `Update Composer Package Name` and `Update Event Library`. Keep commits focused and descriptive; one logical change per commit is preferred. Pull requests should explain the problem, summarize the approach, list verification steps, and link related issues. Include sample CLI output or request/response examples when behavior changes are user-facing.

## Configuration Tips
Copy `.env.example` to `.env` in a consuming app before bootstrapping the framework. Avoid committing secrets. When adding new environment keys or config-dependent services, document the expected variables in `README.MD` or the relevant integration docs.
