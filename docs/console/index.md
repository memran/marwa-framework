# Console Commands

This section covers CLI commands and console development.

## Overview

The Marwa Framework includes a console application for:
- Database management
- Code generation
- Cache management
- Custom commands

## Running Console

```bash
# List all commands
php marwa

# Get help for a command
php marwa help <command>

# Run in specific environment
php marwa --env=production <command>
```

## Built-in Commands

### Database Commands

| Command | Description | Usage |
|---------|-------------|-------|
| `db:create` | Create database | `php marwa db:create <name>` |
| `db:drop` | Drop database | `php marwa db:drop <name>` |
| `db:list` | List databases | `php marwa db:list` |
| `db:list --tables` | List tables | `php marwa db:list --tables` |
| `db:backup` | Backup database | `php marwa db:backup` |
| `db:restore` | Restore database | `php marwa db:restore <path>` |
| `db:optimize` | Optimize tables | `php marwa db:optimize` |
| `db:analyze` | Analyze tables | `php marwa db:analyze` |
| `migrate` | Run migrations | `php marwa migrate` |
| `migrate:rollback` | Rollback migrations | `php marwa migrate:rollback` |
| `migrate:refresh` | Refresh migrations | `php marwa migrate:refresh` |
| `db:seed` | Run seeders | `php marwa db:seed` |

### Make Commands

| Command | Description | Usage |
|---------|-------------|-------|
| `make:command` | Create command | `php marwa make:command UserCommand` |
| `make:controller` | Create controller | `php marwa make:controller UserController` |
| `make:seeder` | Create seeder | `php marwa make:seeder UserSeeder` |
| `make:model` | Create model | `php marwa make:model User` |
| `make:mail` | Create mailable | `php marwa make:mail WelcomeMail` |
| `make:module` | Create module | `php marwa make:module Blog` |

### Cache Commands

| Command | Description | Usage |
|---------|-------------|-------|
| `cache:clear` | Clear cache | `php marwa cache:clear` |

### Config Commands

| Command | Description | Usage |
|---------|-------------|-------|
| `config:cache` | Cache config | `php marwa config:cache` |
| `config:clear` | Clear config cache | `php marwa config:clear` |
| `route:cache` | Cache routes | `php marwa route:cache` |
| `route:clear` | Clear route cache | `php marwa route:clear` |
| `bootstrap:cache` | Cache bootstrap | `php marwa bootstrap:cache` |
| `bootstrap:clear` | Clear bootstrap | `php marwa bootstrap:clear` |

### Module Commands

| Command | Description | Usage |
|---------|-------------|-------|
| `module:cache` | Cache modules | `php marwa module:cache` |
| `module:clear` | Clear modules | `php marwa module:clear` |

### Security Commands

| Command | Description | Usage |
|---------|------------_|-------|
| `security:report` | Security report | `php marwa security:report` |

### Other Commands

| Command | Description | Usage |
|---------|-------------|-------|
| `key:generate` | Generate app key | `php marwa key:generate` |
| `schedule:run` | Run scheduler | `php marwa schedule:run` |
| `schedule:table` | Create schedule table | `php marwa schedule:table` |

## Custom Commands

See [Custom Commands Guide](../developer/console.md).

## Database Management

See [Database Guide](../tutorials/database.md).

## Related

- [Console Development](../developer/console.md) - Create commands
- [Database Commands](../tutorials/database.md) - DB management
- [Quick Start](../getting-started/quick-start.md) - First app