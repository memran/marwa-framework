<?php

declare(strict_types=1);

namespace Marwa\Framework\Database;

use Marwa\DB\Connection\ConnectionManager;
use PDO;
use RuntimeException;

final class DBForge
{
    /** @var array<string, mixed> */
    private $config;

    public function __construct(
        private ConnectionManager $manager,
        private string $connection = 'default'
    ) {
        $this->config = $this->loadConfig();
    }

    /** @return array<string, mixed> */
    private function loadConfig(): array
    {
        $reflection = new \ReflectionClass($this->manager);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        $config = $property->getValue($this->manager);
        return $config->get($this->connection);
    }

    public static function create(ConnectionManager $manager, string $connection = 'default'): self
    {
        return new self($manager, $connection);
    }

    public function connection(): string
    {
        return $this->connection;
    }

    public function pdo(): PDO
    {
        return $this->manager->getPdo($this->connection);
    }

    public function driver(): string
    {
        return $this->manager->getDriver($this->connection);
    }

    public function createDatabase(string $name): bool
    {
        $name = $this->escapeIdentifier($name);
        $driver = $this->driver();

        return match ($driver) {
            'mysql' => $this->execute("CREATE DATABASE IF NOT EXISTS {$name} COLLATE utf8mb4_unicode_ci"),
            'pgsql' => $this->execute("CREATE DATABASE {$name}"),
            'sqlite' => $this->createSqliteDatabase($name),
            default => throw new RuntimeException("Unsupported driver for database creation: {$driver}"),
        };
    }

    public function dropDatabase(string $name): bool
    {
        $name = $this->escapeIdentifier($name);
        $driver = $this->driver();

        return match ($driver) {
            'mysql' => $this->execute("DROP DATABASE IF EXISTS {$name}"),
            'pgsql' => $this->execute("DROP DATABASE IF EXISTS {$name}"),
            'sqlite' => $this->dropSqliteDatabase($name),
            default => throw new RuntimeException("Unsupported driver for database drop: {$driver}"),
        };
    }

    /** @return list<string> */
    public function listDatabases(): array
    {
        $driver = $this->driver();

        return match ($driver) {
            'mysql' => $this->listMysqlDatabases(),
            'pgsql' => $this->listPgsqlDatabases(),
            'sqlite' => $this->listSqliteDatabases(),
            default => throw new RuntimeException("Unsupported driver for listing databases: {$driver}"),
        };
    }

    /** @return list<string> */
    public function listTables(): array
    {
        $driver = $this->driver();

        return match ($driver) {
            'mysql' => $this->listMysqlTables(),
            'pgsql' => $this->listPgsqlTables(),
            'sqlite' => $this->listSqliteTables(),
            default => throw new RuntimeException("Unsupported driver for listing tables: {$driver}"),
        };
    }

    public function tableExists(string $table): bool
    {
        $tables = $this->listTables();
        return in_array($table, $tables, true);
    }

    public function backup(string $path): bool
    {
        $driver = $this->driver();

        return match ($driver) {
            'mysql' => $this->mysqlBackup($path),
            'pgsql' => $this->pgsqlBackup($path),
            'sqlite' => $this->sqliteBackup($path),
            default => throw new RuntimeException("Unsupported driver for backup: {$driver}"),
        };
    }

    public function restore(string $path): bool
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Backup file not found: {$path}");
        }

        $driver = $this->driver();

        return match ($driver) {
            'mysql' => $this->mysqlRestore($path),
            'pgsql' => $this->pgsqlRestore($path),
            'sqlite' => $this->sqliteRestore($path),
            default => throw new RuntimeException("Unsupported driver for restore: {$driver}"),
        };
    }

    public function optimize(): bool
    {
        $driver = $this->driver();

        return match ($driver) {
            'mysql' => $this->mysqlOptimize(),
            'pgsql' => $this->pgsqlVacuum(),
            'sqlite' => $this->sqliteVacuum(),
            default => throw new RuntimeException("Unsupported driver for optimize: {$driver}"),
        };
    }

    public function analyze(): bool
    {
        $driver = $this->driver();

        return match ($driver) {
            'mysql' => $this->mysqlAnalyze(),
            'pgsql' => $this->pgsqlAnalyze(),
            'sqlite' => true,
            default => throw new RuntimeException("Unsupported driver for analyze: {$driver}"),
        };
    }

    public function truncate(string $table): bool
    {
        $table = $this->escapeIdentifier($table);
        return $this->execute("TRUNCATE TABLE {$table}");
    }

    public function dropTable(string $table): bool
    {
        $table = $this->escapeIdentifier($table);
        return $this->execute("DROP TABLE IF EXISTS {$table}");
    }

    public function renameTable(string $from, string $to): bool
    {
        $from = $this->escapeIdentifier($from);
        $to = $this->escapeIdentifier($to);
        $driver = $this->driver();

        return match ($driver) {
            'mysql', 'pgsql' => $this->execute("ALTER TABLE {$from} RENAME TO {$to}"),
            'sqlite' => $this->execute("ALTER TABLE {$from} RENAME TO {$to}"),
            default => throw new RuntimeException("Unsupported driver for rename: {$driver}"),
        };
    }

    private function execute(string $sql): bool
    {
        $this->pdo()->exec($sql);
        return true;
    }

    /** @return list<array<string, mixed>> */
    private function query(string $sql): array
    {
        $stmt = $this->pdo()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function escapeIdentifier(string $name): string
    {
        $driver = $this->driver();

        return match ($driver) {
            'mysql' => '`' . str_replace('`', '``', $name) . '`',
            'pgsql' => '"' . str_replace('"', '""', $name) . '"',
            'sqlite' => '"' . str_replace('"', '""', $name) . '"',
            default => $name,
        };
    }

    private function getDatabaseName(): string
    {
        return $this->config['database'] ?? '';
    }

    private function createSqliteDatabase(string $name): bool
    {
        $path = $this->app()->basePath('database/' . $name . '.sqlite');
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        new PDO('sqlite:' . $path);
        return true;
    }

    private function dropSqliteDatabase(string $name): bool
    {
        $path = $this->app()->basePath('database/' . $name . '.sqlite');
        if (file_exists($path)) {
            unlink($path);
        }
        return true;
    }

    /** @return list<string> */
    private function listMysqlDatabases(): array
    {
        $result = $this->query("SHOW DATABASES");
        return array_column($result, 'Database');
    }

    /** @return list<string> */
    private function listPgsqlDatabases(): array
    {
        $result = $this->query("SELECT datname FROM pg_database WHERE datistemplate = false");
        return array_column($result, 'datname');
    }

    /** @return list<string> */
    private function listSqliteDatabases(): array
    {
        $dbPath = $this->getDatabaseName();
        if ($dbPath === '' || $dbPath === ':memory:') {
            return [];
        }

        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob(dirname($dbPath) . '/*.sqlite');
        return array_map(fn ($f) => basename($f, '.sqlite'), $files ?: []);
    }

    /** @return list<string> */
    private function listMysqlTables(): array
    {
        $db = $this->getDatabaseName();
        $result = $this->query("SHOW TABLES FROM {$this->escapeIdentifier($db)}");
        $key = "Tables_in_{$db}";
        return array_column($result, $key);
    }

    /** @return list<string> */
    private function listPgsqlTables(): array
    {
        $result = $this->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        return array_column($result, 'tablename');
    }

    /** @return list<string> */
    private function listSqliteTables(): array
    {
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        return array_column($result, 'name');
    }

    private function mysqlBackup(string $path): bool
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 3306;
        $user = $this->config['username'] ?? 'root';
        $pass = $this->config['password'] ?? '';
        $db = $this->config['database'] ?? '';

        $cmd = sprintf(
            'mysqldump --host=%s --port=%d --user=%s --password=%s %s > %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($db),
            escapeshellarg($path)
        );

        exec($cmd, $output, $exitCode);
        return $exitCode === 0;
    }

    private function pgsqlBackup(string $path): bool
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 5432;
        $user = $this->config['username'] ?? 'postgres';
        $db = $this->config['database'] ?? '';

        $cmd = sprintf(
            'pg_dump --host=%s --port=%d --user=%s --dbname=%s --file=%s',
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($db),
            escapeshellarg($path)
        );

        exec($cmd, $output, $exitCode);
        return $exitCode === 0;
    }

    private function sqliteBackup(string $path): bool
    {
        $dbPath = $this->getDatabaseName();
        if ($dbPath === '' || $dbPath === ':memory:') {
            throw new RuntimeException("Cannot backup in-memory SQLite database");
        }

        return copy($dbPath, $path);
    }

    private function mysqlRestore(string $path): bool
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 3306;
        $user = $this->config['username'] ?? 'root';
        $pass = $this->config['password'] ?? '';
        $db = $this->config['database'] ?? '';

        $cmd = sprintf(
            'mysql --host=%s --port=%d --user=%s --password=%s %s < %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($db),
            escapeshellarg($path)
        );

        exec($cmd, $output, $exitCode);
        return $exitCode === 0;
    }

    private function pgsqlRestore(string $path): bool
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 5432;
        $user = $this->config['username'] ?? 'postgres';
        $db = $this->config['database'] ?? '';

        $cmd = sprintf(
            'psql --host=%s --port=%d --user=%s --dbname=%s --file=%s',
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($db),
            escapeshellarg($path)
        );

        exec($cmd, $output, $exitCode);
        return $exitCode === 0;
    }

    private function sqliteRestore(string $path): bool
    {
        $dbPath = $this->getDatabaseName();
        if ($dbPath === '' || $dbPath === ':memory:') {
            throw new RuntimeException("Cannot restore to in-memory SQLite database");
        }

        return copy($path, $dbPath);
    }

    private function mysqlOptimize(): bool
    {
        $tables = $this->listTables();
        foreach ($tables as $table) {
            $this->execute("OPTIMIZE TABLE {$this->escapeIdentifier($table)}");
        }
        return true;
    }

    private function pgsqlVacuum(): bool
    {
        $this->execute("VACUUM");
        return true;
    }

    private function sqliteVacuum(): bool
    {
        $this->execute("VACUUM");
        return true;
    }

    private function mysqlAnalyze(): bool
    {
        $tables = $this->listTables();
        foreach ($tables as $table) {
            $this->execute("ANALYZE TABLE {$this->escapeIdentifier($table)}");
        }
        return true;
    }

    private function pgsqlAnalyze(): bool
    {
        $this->execute("ANALYZE");
        return true;
    }

    private function app(): \Marwa\Framework\Application
    {
        return \app();
    }
}