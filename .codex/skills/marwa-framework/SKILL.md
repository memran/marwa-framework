---
name: marwa-framework
description: Repo-specific guidance for working in the Marwa Framework repository, including its bootstrap, HTTP, database, queue, module, and console APIs.
---

# Marwa Framework

Use this skill when working in this repository.

- Read `AGENTS.md` first; it is the authoritative repo policy.
- For architecture or codebase questions, read `graphify-out/GRAPH_REPORT.md` first. If `graphify-out/wiki/index.md` exists, use it instead of raw files.
- Prefer `graphify query`, `graphify path`, or `graphify explain` for cross-module questions.
- Keep changes minimal and backward compatible. Do not edit `vendor/*`.
- Prefer `marwa-support` helpers over custom utilities when an equivalent exists.
- Use the framework surfaces instead of re-implementing them:
  - `Application` and `HttpKernel` for bootstrap and request lifecycle.
  - `Bootstrappers/*` for config, providers, database, modules, and error handling.
  - `Config/*` for env-backed defaults and cache paths.
  - `Middlewares/*` for request-id, session, maintenance, security, router, and debug tooling.
  - `Database/Model.php` for ORM extensions, casts, soft deletes, and audit hooks.
  - `Queue/*` and `Console/Commands/*` for async jobs and operational commands.
  - `Supports/*` and `Supports/Helpers/*` for shared framework utilities.
  - `Modules` and manifests for feature boundaries and dependency loading.
- When changing behavior, check whether it belongs in core, a module, or an adapter before editing.
- After PHP edits, run `composer lint`, then `php vendor/bin/phpunit`, then PHPStan level 6.
- After code changes, run `graphify update .` when possible.
- Build features as modules, keep tests in `tests/`, and keep them deterministic.
- Use [references/framework-api.md](references/framework-api.md) for a concise map of framework capabilities and common extension points.
