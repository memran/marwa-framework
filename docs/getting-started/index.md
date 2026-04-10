# Getting Started

Welcome to the Marwa Framework. This section guides you from installation to your first working application.

## Prerequisites

- **PHP 8.4+**
- **Composer** (package manager)
- **Terminal/Command Line** access

## What's Inside

| Guide | Description | Time |
|-------|-------------|------|
| [Installation](installation.md) | Install and configure the framework | 2 min |
| [Quick Start](quick-start.md) | Build your first working app | 5 min |
| [Project Structure](project-structure.md) | Understand the directory layout | 3 min |

## Installation Steps

### Step 1: Install the Framework

```bash
composer require memran/marwa-framework
```

### Step 2: Create Environment File

```bash
cp .env.example .env
```

### Step 3: Verify Installation

```bash
php marwa
```

You should see the list of available commands.

## First Application

Once installed, follow the [Quick Start](quick-start.md) guide to create your first application.

## Need Help?

- [Troubleshooting](../recipes/troubleshooting.md) - Common issues and solutions
- [Configuration](../reference/config.md) - All configuration options

## Next Steps

After your first app is running, explore:

1. [Controllers](../tutorials/controllers.md) - Handle HTTP requests
2. [Validation](../tutorials/validation.md) - Validate user input
3. [Database](../tutorials/database.md) - Work with databases
4. [Console](../console/commands.md) - CLI commands