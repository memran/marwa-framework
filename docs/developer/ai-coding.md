# AI Coding Guide

## Purpose

Use the built-in AI scaffolding when you want a lightweight application-specific helper that can hold prompts, input shaping, or response formatting logic without coupling the framework to any AI provider.

## Generate Stubs

```bash
php bin/console make:ai-helper SupportAgent --with-command
```

This generates:

- `app/AI/SupportAgent.php`
- `app/Console/Commands/SupportAgentCommand.php`

## Recommended Usage

- Keep provider SDK calls outside the framework core
- Store prompt-building logic in dedicated helper classes
- Treat generated stubs as starting points, not production-ready AI orchestration
- Add tests for prompt shaping, payload normalization, and failure handling in the consuming application
