# Marwa Framework Documentation

Welcome to the official documentation for the Marwa Framework.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Tutorials](#tutorials)
3. [Console](#console)
4. [API Reference](#api-reference)
5. [Architecture](#architecture)
6. [Recipes](#recipes)
7. [Developer](#developer)

---

## Getting Started

New to Marwa? Start here:

| Guide | Description | Time |
|-------|-------------|------|
| [Getting Started](getting-started/index.md) | Overview of getting started | 1 min |
| [Installation](getting-started/installation.md) | Install and configure | 5 min |
| [Quick Start](getting-started/quick-start.md) | Build your first app | 5 min |
| [Project Structure](getting-started/project-structure.md) | Directory layout | 3 min |

---

## Tutorials

Step-by-step guides for common tasks:

| Tutorial | Description |
|----------|-------------|
| [Controllers](tutorials/controllers.md) | Handle HTTP requests |
| [Validation](tutorials/validation.md) | Validate user input |
| [Views](tutorials/view.md) | Twig templates |
| [Models](tutorials/models.md) | Database models |
| [Database](tutorials/database.md) | DB management |
| [Modules](tutorials/modules.md) | Modular runtime, manifests, and module menus |
| [Seeding](tutorials/seeding.md) | Seed database |
| [Security](tutorials/security.md) | Security features |
| [Events](tutorials/events.md) | Event handling |
| [Mail](tutorials/mail.md) | Send emails |
| [Notifications](tutorials/notifications.md) | Notifications |
| [HTTP Client](tutorials/http-client.md) | HTTP requests |
| [DebugBar](tutorials/debugbar.md) | Debug toolbar |

---

## Console

CLI commands and development:

| Guide | Description |
|----------|-------------|
| [Console Overview](console/index.md) | Console commands |
| [Database Commands](tutorials/database.md) | DB management commands |
| [Custom Commands](developer/console.md) | Create commands |

---

## API Reference

Complete API documentation:

| Reference | Description |
|----------|-------------|
| [Reference Index](reference/index.md) | API overview |
| [Application](api/application.md) | Application class |
| [Configuration](api/configuration.md) | Config reference |
| [Facades](api/facades.md) | Facade reference |
| [Helpers](api/helpers.md) | Helper functions |
| [Controllers](api/controllers.md) | Controller reference |
| [Middleware](reference/middleware.md) | Middleware reference |
| [Events](reference/events.md) | Event reference |

---

## Architecture

How the framework works:

| Guide | Description |
|----------|-------------|
| [Architecture](architecture.md) | Design overview |
| [Boot Flow](architecture/boot-flow.md) | Bootstrap flow |
| [Design Principles](architecture/design.md) | Design decisions |

---

## Recipes

Practical guides for common tasks:

| Recipe | Description |
|----------|-------------|
| [Recipes Index](recipes/index.md) | All recipes |
| [Troubleshooting](recipes/troubleshooting.md) | Common issues |
| [Deployment](recipes/deployment.md) | Production deployment |
| [Testing](recipes/testing.md) | Writing tests |

---

## Developer

Notes for contributors:

| Guide | Description |
|----------|-------------|
| [Console Development](developer/console.md) | CLI development |
| [AI Coding Guide](developer/ai-coding.md) | AI-assisted coding |

---

## Quick Reference

### Installation

```bash
composer require memran/marwa-framework
cp .env.example .env
```

### Run Development Server

```bash
php -S localhost:8000 -t public
```

### Run Console

```bash
php marwa
```

### Run Tests

```bash
composer test
```

### Static Analysis

```bash
composer stan
```

---

## Version

Current version: See [`composer.json`](../../composer.json)

---

## Contributing

Contributions welcome! See [GitHub](https://github.com/memran/marwa-framework).

---

## Need Help?

1. [Troubleshooting](recipes/troubleshooting.md) - Common issues
2. [GitHub Issues](https://github.com/memran/marwa-framework/issues) - Report bugs
3. [GitHub Discussions](https://github.com/memran/marwa-framework/discussions) - Ask questions
