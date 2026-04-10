# Database Management (DBForge)

The framework includes DBForge for database-level operations. DBForge complements `marwa-db` by providing commands for managing databases, backups, and optimization.

## Quick Reference

| Command | Description |
|---------|-------------|
| `db:create <name>` | Create a new database |
| `db:drop <name>` | Drop a database |
| `db:list` | List all databases |
| `db:list --tables` | List all tables in current database |
| `db:backup` | Backup database to file |
| `db:restore <path>` | Restore from backup file |
| `db:optimize` | Optimize all tables |
| `db:analyze` | Analyze all tables |

## Usage

### Create a Database

```bash
php marwa db:create myapp
```

### List Databases or Tables

```bash
# List databases
php marwa db:list

# List tables in current database
php marwa db:list --tables
```

### Backup Database

```bash
# Default: saves to database/backups/backup_YYYYMMDD_HHMMSS.sql
php marwa db:backup

# Custom path
php marwa db:backup --path=/path/to/backup.sql
```

### Restore from Backup

```bash
php marwa db:restore backup.sql
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
use Marwa\Framework\Facades\DbForge;

// Create database
DbForge::createDatabase('myapp');

// List tables
$tables = DbForge::listTables();

// Backup to file
DbForge::backup('/path/to/backup.sql');

// Optimize
DbForge::optimize();
```

## Supported Drivers

- **MySQL**: Full support for all operations
- **PostgreSQL**: Full support for all operations
- **SQLite**: Full support for all operations (file-based backup/restore)

## Notes

- Backup uses `mysqldump` for MySQL and `pg_dump` for PostgreSQL (must be installed)
- SQLite backups are simple file copies
- Database operations require the database bootstrapper to be enabled in `config/database.php`