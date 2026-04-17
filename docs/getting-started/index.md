# Getting Started

Welcome to the Marwa Framework. This section guides you from installation to your first working application.

If you want a full application starter instead of assembling the framework package manually, start with [`memran/marwa-php`](https://github.com/memran/marwa-php). It is the recommended starter project for Marwa Framework.

## Prerequisites

- **PHP 8.2+**
- **Composer** (package manager)
- **Terminal/Command Line** access

## Guides

| Guide | Description | Time |
|-------|-------------|------|
| [Installation](installation.md) | Install and configure the framework | 2 min |
| [Quick Start](../tutorials/quick-start.md) | Build your first working app | 5 min |
| [Project Structure](project-structure.md) | Understand the directory layout | 3 min |
| [HTTP Entry Point](http-entry-point.md) | Complete public/index.php examples | 5 min |
| [Complete Tutorial](complete-tutorial.md) | Step-by-step blog application | 15 min |

## Quick Start

### Recommended: Start from the Starter App

```bash
git clone https://github.com/memran/marwa-php.git myapp
cd myapp
composer install
cp .env.example .env
php -S localhost:8000 -t public
```

### Alternative: Install the Framework Package Directly

```bash
composer require memran/marwa-framework
cp vendor/memran/marwa-framework/.env.example .env
```

## Need Help?

- [Troubleshooting](../recipes/troubleshooting.md) - Common issues and solutions
- [Configuration](../reference/config.md) - All configuration options

## Next Steps

After your first app is running, explore:

1. [Controllers](../tutorials/controllers.md) - Handle HTTP requests
2. [Validation](../tutorials/validation.md) - Validate user input
3. [Database](../tutorials/database.md) - Work with databases
4. [Console](../console/commands.md) - CLI commands
