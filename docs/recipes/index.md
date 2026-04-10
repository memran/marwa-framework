# Recipes

This section contains practical guides for common tasks and scenarios.

## What's Inside

| Recipe | Description |
|--------|-------------|
| [Troubleshooting](troubleshooting.md) | Common issues and solutions |
| [Deployment](deployment.md) | Deploy to production |
| [Testing](testing.md) | Write and run tests |

## Popular Recipes

### Getting Started

1. [Installation](../getting-started/installation.md) - Set up the framework
2. [Quick Start](../getting-started/quick-start.md) - First application

### Common Tasks

1. [Database Setup](troubleshooting.md#database) - Database issues
2. [Session Config](troubleshooting.md#session) - Session problems
3. [Email Config](troubleshooting.md#email) - Email not sending

### Production

1. [Deployment](deployment.md) - Go live
2. [Testing](testing.md) - Ensure quality

## Quick Answers

| Problem | Solution |
|---------|----------|
| 500 Error | Check `storage/logs/app.log` |
| Class not found | Run `composer dump-autoload` |
| Database error | Check `.env` DB settings |
| CSS/JS not loading | Check `public/` permissions |

## Next Steps

- [Configuration](../reference/config.md) - All config options
- [API Reference](../reference/index.md) - Complete reference
- [Architecture](../architecture/index.md) - How it works