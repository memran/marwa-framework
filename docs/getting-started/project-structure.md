# Project Structure

This guide explains the Marwa Framework directory structure and what each folder is for.

## Overview

```
marwa-framework/
├── app/                    # Application code (consuming app)
├── bootstrap/             # Framework bootstrap files
├── config/                # Configuration files
├── database/              # Database files
├── public/               # Web root (publicly accessible)
├── resources/           # Application resources
├── routes/              # Route definitions
├── storage/            # Runtime files
├── tests/             # Test files
├── vendor/             # Composer dependencies
├── src/                # Framework source code
├── .env                # Environment variables
├── .env.example       # Environment template
├── composer.json      # Composer manifest
├── phpunit.xml.dist   # PHPUnit configuration
├── phpstan.neon       # PHPStan configuration
└── marwa              # CLI entrypoint
```

## Detailed Breakdown

### `src/` - Framework Core

The framework source code (this repository):

```
src/
├── Adapters/          # Framework adapters
├── Bootstrappers/     # Application bootstrappers
├── Config/           # Configuration classes
├── Console/          # Console commands
├── Contracts/        # Interface definitions
├── Controllers/      # Base controllers
├── Database/         # Database utilities
├── Exceptions/       # Custom exceptions
├── Facades/         # Facade classes
├── Mail/            # Mail utilities
├── Middlewares/     # HTTP middleware
├── Navigation/      # Menu/navigation
├── Notifications/   # Notification system
├── Providers/       # Service providers
├── Queue/          # Queue implementation
├── Scheduling/     # Task scheduling
├── Security/      # Security utilities
├── Stubs/        # File stubs
├── Supports/     # Helper utilities
├── Validation/   # Validation system
├── View/         # View utilities
└── Views/       # View classes
```

### `app/` - Application Code

Your application classes go here:

```
app/
├── Console/Commands/   # Custom CLI commands
├── Controllers/        # HTTP controllers
├── Http/
│   └── Middleware/    # Custom middleware
├── Models/            # Eloquent models
├── Notifications/     # Application notifications
├── Providers/       # Service providers
└── Services/        # Domain services
```

### `bootstrap/` - Bootstrap Files

Framework-internal bootstrap files:

```
bootstrap/
├── cache/             # Cached config, routes, modules
└── app.php           # Application instance (if using)
```

### `config/` - Configuration

All configuration files. Each file corresponds to a specific feature:

```
config/
├── app.php           # App settings, providers, middleware
├── bootstrap.php    # Bootstrap paths config
├── cache.php       # Cache configuration
├── database.php    # Database configuration
├── error.php      # Error handling config
├── event.php      # Event listeners config
├── http.php       # HTTP client config
├── logger.php     # Logging configuration
├── mail.php      # Mail configuration
├── module.php    # Module configuration
├── notification.php # Notification channels
├── queue.php     # Queue configuration
├── schedule.php  # Task scheduling config
├── security.php # Security configuration
├── session.php   # Session configuration
├── storage.php  # File storage config
└── view.php     # View/twig configuration
```

### `database/` - Database Files

```
database/
├── backups/          # Database backups (*.sql or *.sqlite)
├── migrations/      # Database migrations
├── seeders/        # Database seeders
└── app.sqlite      # SQLite database file (example)
```

### `public/` - Web Root

Only this directory should be publicly accessible:

```
public/
├── index.php        # HTTP entrypoint
├── .htaccess      # Apache rewrite rules
└── favicon.ico    # Favicon
```

### `resources/` - Resources

```
resources/
└── views/                 # Twig templates
    ├── layouts/          # Layout templates
    └── themes/           # Theme files
```

### `routes/` - Route Definitions

```
routes/
├── web.php           # Web routes (browser requests)
├── api.php          # API routes (REST endpoints)
└── console.php     # Console routes (CLI closures)
```

### `storage/` - Runtime Storage

**Must be writable by the web server:**

```
storage/
├── app/               # Application files
│   └── public/       # Publicly accessible uploads
├── cache/            # Framework cache
│   ├── framework/  # View cache, etc.
│   └── views/     # Compiled Twig templates
├── framework/       # Framework runtime files
│   └── schedule/  # Scheduler lock files
└── logs/           # Log files
    └── app.log     # Application logs
```

### `tests/` - Test Files

```
tests/
├── Fixtures/        # Test fixtures and helpers
├── Unit/           # Unit tests
└── Feature/       # Feature/integration tests
```

## File Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Controllers | `*Controller.php` | `UserController.php` |
| Models | `*Model.php` or single word | `User.php`, `PostModel.php` |
| Commands | `*Command.php` | `MakeUserCommand.php` |
| Seeders | `*Seeder.php` | `UserSeeder.php` |
| Middleware | `*Middleware.php` | `AuthMiddleware.php` |
| Providers | `*ServiceProvider.php` | `AppServiceProvider.php` |
| Notifications | `*Notification.php` | `OrderShippedNotification.php` |
| Views | `*.twig` | `home.twig`, `layout.twig` |

## Creating an Application

When you create a new application, your structure should be:

```
myapp/
├── app/                    # Your application code
│   ├── Console/Commands/
│   ├── Controllers/
│   ├── Http/Middleware/
│   ├── Models/
│   └── Providers/
├── bootstrap/cache/        # Framework cache
├── config/                # Your config files
├── database/
│   ├── migrations/
│   └── seeders/
├── public/                # Web root
│   └── index.php
├── resources/
│   └── views/
├── routes/
│   ├── web.php
│   └── api.php
├── storage/               # Must be writable
│   ├── app/
│   ├── cache/
│   └── logs/
├── tests/
├── .env                   # Your environment
├── composer.json
└── marwa                  # CLI entrypoint
```

## Best Practices

1. **Keep `app/` organized** - Use subdirectories by feature
2. **Don't touch `vendor/`** - Never modify dependencies
3. **Use `storage/`** - Store generated files, uploads, logs there
4. **Environment-specific** - Use `.env` for local configuration
5. **Config caching** - In production, cache config with `php marwa config:cache`

## Next Steps

- [Installation](installation.md) - Set up the framework
- [Quick Start](../tutorials/quick-start.md) - Build your first app
- [Configuration](../reference/config.md) - Customize config
