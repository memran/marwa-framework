# Contributing

Contributions are welcome through focused pull requests.

## Workflow

1. Fork the repository and create a feature branch.
2. Keep changes small and scoped to one concern.
3. Add or update tests when behavior changes.
4. Run the local quality checks before opening a PR.

## Local Checks

```bash
composer test
composer analyse
composer lint
```

Run `composer lint` after manual PHP edits and before submitting a PR. Treat PHP CS Fixer output as the source of truth for whitespace, import order, braces, and blank lines.

## Coding Expectations

- Target PHP 8.2+ and keep `declare(strict_types=1);`
- Prefer small, focused classes and explicit return types
- Avoid breaking public behavior unless fixing a bug or security issue
- Keep documentation and examples aligned with actual repository behavior

## Pull Requests

Each pull request should include:

- A short problem statement
- A concise change summary
- Verification steps
- Linked issue, if applicable

See [AGENTS.md](AGENTS.md) for repository-specific contributor guidance.
