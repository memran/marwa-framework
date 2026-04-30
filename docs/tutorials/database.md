# Database Management (DBForge)

The framework includes DBForge for database-level operations. DBForge complements `marwa-db` by providing commands for managing databases, backups, and optimization.

Database commands accept a `--connection` option so you can target a non-default connection when your app defines more than one database config.

## Quick Reference

| Command | Description |
|---------|-------------|
| `db:create <name> [--connection=...]` | Create a new database |
| `db:drop <name> [--connection=...]` | Drop a database |
| `db:list [--tables] [--connection=...]` | List databases or tables |
| `db:backup [--path=...] [--connection=...]` | Backup database to file |
| `db:restore <path> [--connection=...]` | Restore from backup file |
| `db:optimize [--connection=...]` | Optimize all tables |
| `db:analyze [--connection=...]` | Analyze all tables |

## Usage

### Create a Database

```bash
php marwa db:create myapp

# Use a named connection
php marwa db:create myapp --connection=reporting
```

### List Databases or Tables

```bash
# List databases
php marwa db:list

# List tables in current database
php marwa db:list --tables

# Inspect a named connection
php marwa db:list --connection=reporting
```

### Backup Database

```bash
# Default: saves to database/backups/backup_YYYYMMDD_HHMMSS.sql
php marwa db:backup

# Custom path
php marwa db:backup --path=/path/to/backup.sql

# Target a named connection
php marwa db:backup --connection=reporting
```

### Restore from Backup

```bash
php marwa db:restore backup.sql

php marwa db:restore backup.sql --connection=reporting
```

### Optimize Tables

```bash
php marwa db:optimize
```

- MySQL: Runs `OPTIMIZE TABLE` on all tables
- PostgreSQL: Runs `VACUUM`
- SQLite: Runs `VACUUM`

### Analyze Tables

```bash
php marwa db:analyze
```

- MySQL: Runs `ANALYZE TABLE` on all tables
- PostgreSQL: Runs `ANALYZE`
- SQLite: No-op (automatic)

### Drop a Database

```bash
php marwa db:drop myapp --force
```

## Programmatic Usage

```php
use Marwa\Framework\Facades\DatabaseForge;

// Create database
DatabaseForge::createDatabase('myapp');

// List tables
$tables = DatabaseForge::listTables();

// Backup to file
DatabaseForge::backup('/path/to/backup.sql');

// Optimize
DatabaseForge::optimize();
```

## Supported Drivers

- **MySQL**: Full support for all operations
- **PostgreSQL**: Full support for all operations
- **SQLite**: Full support for all operations (file-based backup/restore)

## Notes

- Backup uses `mysqldump` for MySQL and `pg_dump` for PostgreSQL (must be installed)
- SQLite backups are simple file copies
- Database operations require the database bootstrapper to be enabled in `config/database.php`
