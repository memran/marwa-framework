# Repository Guidelines

<!-- Last scope wins. Folder AGENTS.md overrides this. Keep this file short and verifiable. -->

## What this repo is

- `memran/marwa-framework` is a **library**, not an app. There is no `public/`, `marwa` CLI file, or `modules/` directory here — those belong to consuming apps (recommended skeleton: `memran/marwa-php`).
- All `Marwa\Framework\*` code is in `src/` (PSR-4). Everything else — `Marwa\DB\*`, `Marwa\Support\*`, `Marwa\Module\*`, `Marwa\Router\*`, `Marwa\View\*`, `Marwa\Entity\*`, `Marwa\Event\*`, `Marwa\Logger\*` — comes from separate `memran/*` packages in `vendor/`.
- Vendor packages are wrapped by `src/Adapters/` (AI, Cache, Event, Http, Logger, Mail, MCP, Process, Validation, Router, View, Debugbar, ErrorHandler). New integrations go through adapters, never vendor edits.
- Core entrypoints: `src/Application.php` (container, env, config, providers) and `src/HttpKernel.php` (middleware pipeline). The first `Application` constructed becomes `$GLOBALS['marwa_app']` (later ones don't replace it); `src/Supports/Helpers/` resolves services through it, so tests unset the global between apps.
- A concise API map already exists in `.opencode/skill/marwa-framework/SKILL.md` and `.codex/skills/marwa-framework/references/framework-api.md`; deep docs live in `docs/`. Prefer these over re-deriving structure.

## Commands

- `composer test` — full PHPUnit 11.5 suite.
  - one file: `php vendor/bin/phpunit tests/ModuleBootstrapperTest.php`
  - one test: `php vendor/bin/phpunit --filter=testName`
- `composer stan` (= `composer analyse`) — PHPStan level 6. Level, paths, and ignores live in `phpstan.neon`; don't pass those flags again.
- `composer lint` — PHP CS Fixer **dry-run**; `composer fix` — apply formatting. Never hand-format around lint output.
- `composer ci` — full gate in CI order: PHPStan → CS Fixer dry-run → PHPUnit. Extra `bin/ci` args are forwarded to PHPUnit.
- `composer test:coverage` — coverage run.
- Finish changes with `composer ci` green.
- PHP CS Fixer runs on newer local PHP than the 8.2 platform pin; composer scripts deliberately pass `--allow-unsupported-php-version=yes`. Leave it in place.

## Testing

- `phpunit.xml.dist` enables `failOnRisky`, `failOnWarning`, `beStrictAboutOutputDuringTests` — tests must not emit output, warnings, or risky behavior.
- `tests/bootstrap.php` forces HTTP mode via `Runtime::setConsoleOverride(false)` and redirects sessions to `sys_get_temp_dir()`. CLI-mode tests flip the override and restore it.
- Tests build throwaway apps: temp base path with `.env`, `config/*.php`, and fixtures, then unlink everything in `tearDown()` and unset `$GLOBALS['marwa_app']`, `$_ENV`/`$_SERVER` keys they set.
- Call `restore_error_handler()` / `restore_exception_handler()` after tests that swap handlers.
- Fixtures live in `tests/Fixtures/` (e.g. `Modules/` drives `ModuleBootstrapperTest`). Test namespace `Marwa\Framework\Tests\` maps to `tests/` via autoload-dev.
- Add a regression test when fixing a bug; cover new public service behavior.

## Conventions

- `declare(strict_types=1);` in every file. Target PHP 8.2 (`config.platform` pins 8.2.30; CI runs 8.2 and 8.3).
- `.php-cs-fixer.dist.php` is the style source of truth: `@PSR12`, strict types, no unused imports, ordered imports, single-line empty bodies.
- Prefer `marwa-support` utilities (`Arr`, `Str`, `Collection`, `Json`, `Date`, `File`, `Crypt`, `Hash`, `Validator`, `Sanitizer`, `Security`, `XSS`, ...) over custom helpers. `src/Supports/Helpers.php` only re-exports function files from `src/Supports/Helpers/`.
- Validation engine is `Marwa\Support\Validation\RequestValidator` + `RuleRegistry` (vendor `marwa-support`); `src/Validation/` keeps backward-compatible framework adapters (`RequestValidator`, `FormRequest`).
- Modules are manifest-driven through `marwa-module`. Framework-side wiring: `src/Bootstrappers/ModuleBootstrapper.php`, `src/Config/ModuleConfig.php`, `src/Console/Commands/Module*`. App module dirs/manifests are not in this repo — only test fixtures.
- Build app-facing features as modules, not core; keep core module-agnostic.
- Cache tooling is paired: `config:cache`/`config:clear`, `route:cache`/`route:clear`, `module:cache`/`module:clear`, `bootstrap:cache`/`bootstrap:clear`.

## Never

- Edit `vendor/*` or any `memran/*` package source — changes belong in that package's repo.
- Commit `.env` or secrets. `composer.lock` is intentionally gitignored here.

## graphify

The knowledge graph lives in `graphify-out/` (gitignored).
- Read `graphify-out/GRAPH_REPORT.md` before architecture questions; if `graphify-out/wiki/index.md` exists, navigate it instead of raw files.
- Prefer `graphify query "<question>"`, `graphify path "<A>" "<B>"`, `graphify explain "<concept>"` for cross-module questions.
- After code changes, run `graphify update .` to refresh it.
