# Repository Guidelines

<!-- Last scope wins. Folder AGENTS.md overrides this. Keep <500 lines. Review monthly. -->

## Project

Marwa Framework is a lightweight, PSR-aligned PHP 8.2+ framework core for modular web apps, console tools, and reusable runtime services.

## Structure

- Namespace: `Marwa\Framework\`
- Core: `src/`
- Top-level `src/` folders: `Adapters/`, `Bootstrappers/`, `Config/`, `Console/`, `Contracts/`, `Controllers/`, `Database/`, `Exceptions/`, `Facades/`, `Mail/`, `Middlewares/`, `Navigation/`, `Notifications/`, `Providers/`, `Queue/`, `Scheduling/`, `Security/`, `Stubs/`, `Supports/`, `Validation/`, `View/`, `Views/`
- Key files: `src/Application.php`, `src/HttpKernel.php`
- Routes: `routes/web.php`
- CLI entrypoint: `marwa`
- Tests: `tests/`
- Helpers: `src/Supports/Helpers.php` (re-exports modular helpers from `src/Supports/Helpers/`)
- Validation: `src/Validation/` with `ValidationRule/` (27 rule classes) and `Helpers/` (8 helper classes)
- DB Library: `memran\marwa-db`
- Support Library: `memran\marwa-support`
- Debugbar: `memran\marwa-debugbar`
- View: `memran\marwa-view`
- Module: `memran\marwa-module`

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
- Keep files small: max 200 lines/class, 20 lines/method
- Use constants and enums for finite states

## Engineering Principles

- KISS, DRY, SOLID
- Understand context before coding
- Prefer composition over inheritance
- Keep architecture modular and decoupled
- Write production-ready, maintainable, scalable code
- Prefer clarity over cleverness
- Align with project architecture
- Edit existing code over creating duplicates
- Maintain backward compatibility
- Keep changes minimal and scoped
- Validate all inputs
- Use composer packages by creating adapter

## Testing

- Add tests in `tests/`
- Use `*Test.php` or `*_test.php`
- Cover routing, bootstrapping, middleware, and adapters
- Run `composer test`, then `composer stan`
- Aim for 80% minimum coverage
- Every public service method needs unit tests

## Commit & PR

- Use short, imperative commit subjects
- Keep commits focused: one logical change per commit
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

## Error Handling

- Use centralized exception handling
- Log critical errors
- Fail gracefully with meaningful responses

## Performance

- Optimize for readability first
- Avoid premature optimization
- Cache where necessary

## Documentation

- Update `docs/*` when code changes require it
- Keep explanations useful
- Add section anchors for navigation
- Add examples
- Add diagrams only when they help

## Versioning

- Version: `v1.0.0`
- Updated: `2026-04-17`

## Change Log

- `2026-04-17` - Refactored Helpers.php into modular files:
  - Extracted 8 helper categories into `src/Supports/Helpers/` directory
  - `Helpers.php` reduced from 544 to 16 lines (re-exports only)
  - New files: Paths, Container, SessionRequest, Services, Security, ValidationResponse, ViewDebug, Utilities
  - 100% backward compatible
- `2026-04-17` - Refactored Validation system:
  - Extracted validation rules into `src/Validation/ValidationRule/` directory
  - Created `RuleRegistry` for rule management with custom rule support
  - Extracted helper classes to `src/Validation/Helpers/`
  - Reduced `RequestValidator.php` from 687 to 245 lines (64% reduction)
  - Added support for custom validation rules via constructor or method injection
- `2026-04-17` - Updated workflows
