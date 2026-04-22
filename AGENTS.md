# Repository Guidelines

<!-- Last scope wins. Folder AGENTS.md overrides this. Keep under 500 lines. Review monthly. -->

## Project

Marwa Framework is a lightweight, PSR-aligned PHP 8.2+ framework core for modular web apps, console tools, and reusable runtime services.

## Structure

- Namespace: `Marwa\Framework\`
- Core: `src/`
- Main folders: `Adapters/`, `Bootstrappers/`, `Config/`, `Console/`, `Contracts/`, `Controllers/`, `Database/`, `Exceptions/`, `Facades/`, `Mail/`, `Middlewares/`, `Navigation/`, `Notifications/`, `Providers/`, `Queue/`, `Scheduling/`, `Security/`, `Stubs/`, `Supports/`, `Validation/`, `View/`, `Views/`
- Key files: `src/Application.php`, `src/HttpKernel.php`
- Routes: `routes/web.php`
- CLI entrypoint: `marwa`
- Tests: `tests/`
- Helpers: `src/Supports/Helpers.php` re-exports modular helpers from `src/Supports/Helpers/`
- Validation: `Marwa\Support\Validation` is the canonical validation engine; `src/Validation/` keeps backward-compatible framework adapters
- DB library: `memran\marwa-db`
- Support library: `memran\marwa-support`
- Debugbar: `memran\marwa-debugbar`
- View: `memran\marwa-view`
- Module: `memran\marwa-module`
- Entity: `memran\marwa-entity` is the source of truth for schema and validation across controllers, requests, models, migrations, and views
- Events: `memran\marwa-event`
- Router : `memran\marwa-router`
- Logger : `memran\marwa-logger`
- Error handler: `memran\marwa-error-handler`

## Marwa Module Library

`marwa-module` is the modular unit of the system. Each module must be self-contained and feature-based.

### Structure

Modules may contain controllers, models, views, routes, config, migrations, resources, policies, supports, actions, entities, widgets, and services.

### Registration

Each module must provide a manifest with name, dependencies, permissions, widgets, routes, migrations, menu, and status.

### Dependency Rules

- Declare dependencies explicitly
- Load independent modules before dependent modules

### Agent Rules

- Build features as modules, not core
- Keep modules decoupled and reusable
- Avoid cross-module tight coupling
- Use manifests for integrations

## Marwa-Support Library

Use `marwa-support` utility classes for common operations. Do not write custom helpers when an equivalent exists.

### Available Classes

`Arr`, `CSRF`, `Collection`, `Crypt`, `Date`, `File`, `Finder`, `Hash`, `Helper`, `Html`, `Json`, `Number`, `Obj`, `Random`, `Sanitizer`, `Security`, `Str`, `Url`, `Validation`, `Validator`, `XSS`

### Usage Examples

```php
Str::slug($title);
Arr::get($data, 'user.name');
Validator::make($input, $rules);
Hash::make($password);
Url::to('/dashboard');
```

### Agent Instructions

- Pick the required utility first, then use the matching class
- Avoid mixing utilities unnecessarily
- Validate and sanitize input with `Validator`, `Sanitizer`, `XSS`, or `Security`
- Use `Collection` for array transformations
- Use `Str`, `Arr`, and `Obj` for data handling

## Commands

- `composer install` - install dependencies
- `composer test` - run PHPUnit
- `composer stan` - run PHPStan level 6
- `php marwa` - run the CLI
- `php -S localhost:8000 -t public` - local manual check

## Style

- `declare(strict_types=1);`
- PSR-1, PSR-12, PSR-4
- 4-space indentation
- Typed properties and explicit return types
- PascalCase classes
- `*Interface`, `*Exception`, `*ServiceProvider`
- Prefer small, single-purpose classes
- Keep files small: max 200 lines per class, 20 lines per method
- Use constants and enums for finite states

## Engineering Principles

- KISS, DRY, SOLID
- Understand context before coding
- Prefer composition over inheritance
- Keep architecture modular and decoupled
- Write production-ready, maintainable, scalable code
- Prefer clarity over cleverness
- Align with project architecture
- Edit existing code instead of creating duplicates
- Maintain backward compatibility
- Keep changes minimal and scoped
- Validate all inputs
- Use composer packages by creating adapters
- Choose the correct class for the responsibility
- Keep code clean and minimal
- Avoid duplicating utility logic
- Check before writing code to avoid duplicate code
- Every package has agents.md and README.md for Learning

## Testing

- Add tests in `tests/`
- Use `*Test.php` or `*_test.php`
- Cover routing, bootstrapping, middleware, and adapters
- Prefer `php vendor/bin/phpunit` for direct verification and `composer test` for the repo shortcut
- Prefer `php vendor/bin/phpstan analyse --level 6 --memory-limit=1G --no-progress` for static analysis and `composer stan` for the repo shortcut
- Run PHPUnit first, then PHPStan level 6 before considering a change complete
- Aim for 80% minimum coverage
- Every public service method needs unit tests
- For PHPUnit tests:
  - Use `declare(strict_types=1);` in every test file
  - Prefer `final class ... extends TestCase`
  - Keep tests isolated and deterministic
  - Use temp directories under `sys_get_temp_dir()` for filesystem work
  - Clean up in `tearDown()` for every file, directory, env var, and global created by the test
  - Avoid network, time, randomness, and external services unless explicitly mocked
  - Prefer explicit assertions:
    - `assertSame()` for scalars and arrays
    - `assertTrue()` / `assertFalse()` for booleans
    - `assertNull()` / `assertNotNull()` for nullable values
    - `assertInstanceOf()` for types
    - `assertFileExists()` / `assertDirectoryExists()` for filesystem effects
  - Test behavior, not implementation details
  - Add a regression test whenever fixing a bug
  - Keep assertions precise and minimal
  - Do not depend on test order
  - Do not allow warnings, risky tests, or stray output
- For PHPStan:
  - Treat level 6 as the minimum acceptable standard
  - Prefer explicit typing: typed properties, explicit parameter types, explicit return types, and `declare(strict_types=1);`
  - Avoid `mixed` unless a boundary truly requires it
  - Use accurate array shapes in PHPDoc when a method returns structured arrays
  - Add PHPDoc only when types cannot be expressed directly in code
  - Prefer concrete types over generic containers
  - Fix the source code rather than suppressing warnings
  - Keep ignores narrowly scoped and documented when unavoidable
  - Watch for nullable/union mismatches, missing return types, undefined array keys, invalid access, and iterable value ambiguity

## Commit & PR

- Use short, imperative commit subjects
- Keep commits focused on one logical change
- PRs should explain the problem, approach, and verification
- Link related issues
- Include CLI output or request/response examples when user-facing behavior changes

## Configuration

- Copy `.env.example` to `.env` in consuming apps
- Never commit secrets
- Document new env keys in `README.MD` or the relevant docs

## Never

- Never change `vendor/*`
- Never expose secrets or passwords
- Suggest changes to vendor code instead of editing it
- Never run raw SQL operations

## Error Handling

- Use centralized exception handling
- Log critical errors
- Fail gracefully with meaningful responses

## Performance

- Optimize for readability first
- Avoid premature optimization
- Cache where necessary

## Documentation

- Documentation lives in `docs/*`
- Update docs when code changes require it
- Keep explanations useful
- Add section anchors for navigation
- Add examples
- Add diagrams only when they help

## Versioning

- Version: `v1.0.0`
- Updated: `2026-04-18`

## Change Log

- `2026-04-18` - Cleaned wording, removed duplication, and aligned validation guidance with the support-backed engine
- `2026-04-17` - Refactored Helpers.php into modular files
  - Extracted 8 helper categories into `src/Supports/Helpers/`
  - `Helpers.php` reduced from 544 to 16 lines
  - New files: `Paths`, `Container`, `SessionRequest`, `Services`, `Security`, `ValidationResponse`, `ViewDebug`, `Utilities`
  - Backward compatible
- `2026-04-17` - Refactored validation system
  - Extracted validation rules into `src/Validation/ValidationRule/`
  - Created `RuleRegistry` for rule management with custom rule support
  - Extracted helper classes to `src/Validation/Helpers/`
  - Reduced `RequestValidator.php` from 687 to 245 lines
  - Added support for custom validation rules via constructor or method injection
- `2026-04-17` - Updated workflows
