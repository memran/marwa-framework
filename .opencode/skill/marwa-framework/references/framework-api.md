# Marwa Framework API Map

This repo is a lightweight PHP 8.2+ framework core. Use the existing framework surfaces instead of inventing new helpers.

## Core bootstrap

- `src/Application.php`
  - Owns the container, console kernel, router, scheduler, and lifecycle dispatch.
  - Use it to resolve framework services, boot the app, and trigger lifecycle events.
- `src/HttpKernel.php`
  - Handles request globals, event dispatch, and the middleware pipeline.
  - Keep request-path changes here small and measurable.
- `src/Bootstrappers/*`
  - `AppBootstrapper` loads config and providers.
  - `CoreBindingsBootstrapper` binds shared services.
  - `DatabaseBootstrapper` wires DB services.
  - `ModuleBootstrapper` loads module manifests and module services.
  - `ErrorHandlerBootstrapper` wires centralized error handling.

## Configuration

- `src/Config/*`
  - `AppConfig` defines middleware order, provider defaults, debugbar settings, and collectors.
  - `BootstrapConfig` defines cache file paths and cache policy.
  - `SecurityConfig`, `SessionConfig`, `QueueConfig`, `DatabaseConfig`, `ViewConfig`, and related config classes should own env-backed defaults for their subsystem.
- `src/Supports/Config.php`
  - Loads config files, primes merged cache payloads, and serves dot-notation access.
  - Use `loadIfExists()` for optional config files and `prime()` for cache payloads.

## HTTP and middleware

- `src/Middlewares/*`
  - `RequestIdMiddleware` for request correlation.
  - `SessionMiddleware` for session startup and session config.
  - `MaintenanceMiddleware` for maintenance mode responses.
  - `SecurityMiddleware` for security checks and headers.
  - `RouterMiddleware` for routing dispatch.
  - `DebugbarMiddleware` for debug output injection when debug tooling is enabled.
- Prefer middleware for request-scoped behavior instead of adding logic directly to controllers.

## Database and ORM

- `src/Database/Model.php`
  - Framework ORM base built on `memran/marwa-db`.
  - Extensions include convenience aliases, cast normalization, soft delete handling, and audit lifecycle events.
  - Prefer model-level hooks over duplicating DB access patterns in controllers.
- Common model APIs:
  - `newQuery()`, `tableName()`, `useConnection()`
  - `findBy()`, `saveOrFail()`, `deleteOrFail()`
  - `create()`, `forceFill()`, `hydrateRow()`, `destroy()`
  - audit hooks such as `onRestoring()`, `onRestored()`, `onForceDeleting()`, `onForceDeleted()`, `onDestroying()`, `onDestroyed()`

## Queue and console

- `src/Queue/*`
  - `FileQueue` is the file-backed queue implementation.
  - Keep queue payloads serializable and deterministic.
- `src/Console/Commands/*`
  - Use commands for maintenance and build-time tasks such as cache generation, module maintenance, and scaffold generation.
  - Keep command output explicit and idempotent when possible.

## Modules and services

- Modules are the primary feature boundary.
- Keep module manifests self-contained.
- Load independent modules before dependent modules.
- Put module-specific controllers, models, migrations, views, services, and commands inside the module, not in core.

## Support utilities

- `src/Supports/Helpers.php` re-exports modular helpers from `src/Supports/Helpers/`.
- Prefer the utility class that matches the task:
  - `Arr`, `Str`, `Obj` for data handling
  - `Validator`, `Sanitizer`, `Security`, `XSS` for input handling
  - `Url`, `Html`, `Json`, `Hash`, `Crypt`, `Date`, `File`, `Collection`, `Finder`, `Random` as appropriate
- Avoid custom helper functions when a support class already exists.

## Verification

- Run `composer lint` after PHP edits.
- Run `php vendor/bin/phpunit` and PHPStan level 6 before considering a change complete.
- Add regression tests for any bug fix.
- Prefer deterministic tests and temp directories under `sys_get_temp_dir()`.
