# Project Structure

This guide explains the directory structure and what each folder is for.

## Overview

```
myapp/
├── app/                    # Application code
├── bootstrap/             # Framework bootstrap files
├── config/                # Configuration files
├── database/              # Database files
│   ├── backups/          # Database backups
│   ├── migrations/      # Database migrations
│   └── seeders/        # Database seeders
├── public/               # Web root (publicly accessible)
├── resources/           # Application resources
│   └── views/         # View templates
├── routes/              # Route definitions
├── storage/            # Runtime files
│   ├── app/          # Application storage
│   ├── cache/       # Cache files
│   ├── framework/    # Framework storage
│   └── logs/        # Log files
├── vendor/             # Composer dependencies
├── .env              # Environment variables
├── composer.json     # Composer manifest
└── marwa            # CLI entrypoint
```

## Detailed Breakdown

### `app/` - Application Code

Your application classes go here. By default:

```
app/
├── Console/Commands/   # Custom CLI commands
├── Controllers/        # HTTP controllers
├── Models/            # Eloquent models
└── Providers/         # Service providers
```

### `bootstrap/` - Bootstrap Files

Framework-internal files:

```
bootstrap/
├── cache/             # Cached config and routes
└── app.php           # Application instance (if using)
```

### `config/` - Configuration

All configuration files. See [Configuration Reference](../reference/config.md):

```
config/
├── app.php           # App settings, providers, middleware
├── cache.php        # Cache config
├── console.php      # Console commands config
├── database.php   # Database config
├── debugbar.php   # Debug bar config
├── guard.php      # Auth guard config
├── http.php      # HTTP config
├── logging.php   # Logging config
├── mail.php      # Mail config
├── module.php    # Module config
├── notification.php  # Notification channels
├── queue.php     # Queue config
├── session.php   # Session config
├── view.php     # View config
└── security.php # Security config
```

### `database/` - Database Files

```
database/
├── backups/          # DBForge backups (*.sql or *.sqlite)
├── migrations/       # marwa-db migrations
├── seeders/         # Database seeders
└── app.sqlite       # SQLite database file (if using)
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
├── views/                 # Twig templates
│   ├── layouts/          # Layout templates
│   └── themes/           # Theme files
└── assets/               # CSS, JS, images
```

### `routes/` - Route Definitions

```
routes/
├── web.php           # Web routes
├── api.php          # API routes
└── console.php     # Console routes (closures)
```

### `storage/` - Runtime Storage

**Must be writable:**

```
storage/
├── app/               # Application files
│   └── public/       # Publicly accessible files
├── cache/            # Framework cache
│   ├── framework/  # View cache, etc.
│   └── views/       # Compiled views
└── logs/            # Log files
    └── app.log      # Application logs
```

## File Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Controllers | `*Controller.php` | `UserController.php` |
| Models | Single word | `User.php` |
| Commands | `*Command.php` | `MakeUserCommand.php` |
| Seeders | `*Seeder.php` | `UserSeeder.php` |
| Middleware | `*Middleware.php` | `AuthMiddleware.php` |
| Views | `*.twig` | `home.twig` |

## Customizing the Structure

### Changing Default Paths

In `config/app.php`:

```php
return [
    'paths' => [
        'app' => base_path('custom-app'),
        'config' => base_path('custom-config'),
        'storage' => base_path('custom-storage'),
    ],
];
```

### Adding Directories

The framework is flexible. Add directories as needed:

```
app/
├── Services/     # Domain services
├── Repositories/ # Data repositories
├── Transformers/# API transformers
└── Validators/   # Custom validators
```

## Best Practices

1. **Keep `app/` clean** - Use subdirectories for organization
2. **Don't touch `vendor/`** - Never modify dependencies
3. **Use `storage/`** - Store generated files there
4. **Environment-specific** - Use `.env` for local changes

## Next Steps

- [Installation](installation.md) - Set up the framework
- [Quick Start](quick-start.md) - Build your first app
- [Configuration](../reference/config.md) - Customize config