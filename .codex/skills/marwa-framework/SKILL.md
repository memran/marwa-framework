---
name: marwa-framework
description: Repo-specific guidance for working in the Marwa Framework repository.
---

# Marwa Framework

Use this skill when working in this repository.

- Read `AGENTS.md` first; it is the authoritative repo policy.
- For architecture or codebase questions, read `graphify-out/GRAPH_REPORT.md` first. If `graphify-out/wiki/index.md` exists, use it instead of raw files.
- Prefer `graphify query`, `graphify path`, or `graphify explain` for cross-module questions.
- Keep changes minimal and backward compatible. Do not edit `vendor/*`.
- Prefer `marwa-support` helpers over custom utilities when an equivalent exists.
- After PHP edits, run `composer lint`, then `php vendor/bin/phpunit`, then PHPStan level 6.
- After code changes, run `graphify update .` when possible.
- Build features as modules, keep tests in `tests/`, and keep them deterministic.
