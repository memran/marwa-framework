<?php

declare(strict_types=1);

namespace Memran\MarwaDb;

use Memran\MarwaDb\Schema\Blueprint;

class Database
{
    public static function connection(string $name): self
    {
        return new self();
    }

    public function table(string $name): \Memran\MarwaDb\QueryBuilder
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function schema(): \Memran\MarwaDb\Schema\SchemaBuilder
    {
        throw new \BadMethodCallException('Stub only');
    }
}

namespace Memran\MarwaDb\Schema;

class Blueprint
{
    public function id(string $name, int $length = 32): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function string(string $name, int $length = 255): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function text(string $name): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function integer(string $name): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function nullable(): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function default(mixed $value): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function primary(): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @param list<string> $columns
     */
    public function index(array $columns, ?string $name = null): self
    {
        throw new \BadMethodCallException('Stub only');
    }
}

class SchemaBuilder
{
    public function hasTable(string $name): bool
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @param \Closure(Blueprint): void $callback
     */
    public function create(string $name, \Closure $callback): void
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function drop(string $name): void
    {
        throw new \BadMethodCallException('Stub only');
    }
}

#[\PHPStan\ShouldNotBePublic]
class QueryBuilder
{
    public function where(string $column, string $operator, mixed $value): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function whereNull(string $column): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function whereNotNull(string $column): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function get(): array
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function insert(array $data): int
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function update(array $data): int
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function delete(): int
    {
        throw new \BadMethodCallException('Stub only');
    }
}

namespace Marwa\DB\Connection;

use Marwa\DB\Query\Builder;

/**
 * @phpstan-stub
 */
class ConnectionManager
{
    public function table(string $table): Builder
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function getPdo(string $connection = 'default'): \PDO
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function getDriver(string $connection = 'default'): string
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @param \Closure(): mixed $callback
     */
    public function transaction(\Closure $callback, string $connection = 'default'): mixed
    {
        throw new \BadMethodCallException('Stub only');
    }
}

namespace Marwa\DB\Schema;

/**
 * @phpstan-stub
 */
class Schema
{
    /**
     * @param \Closure(Marwa\DB\Schema\Blueprint): void $callback
     */
    public static function create(string $table, \Closure $callback): void
    {
        throw new \BadMethodCallException('Stub only');
    }

    public static function drop(string $table): void
    {
        throw new \BadMethodCallException('Stub only');
    }
}

namespace Marwa\DB\Schema;

/**
 * @phpstan-stub
 */
class Blueprint
{
    public function id(string $name, int $length = 32): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function string(string $name, int $length = 255): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function text(string $name): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function integer(string $name): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function nullable(): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function default(mixed $value): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function primary(): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @param list<string> $columns
     */
    public function index(array $columns, ?string $name = null): self
    {
        throw new \BadMethodCallException('Stub only');
    }
}

namespace Marwa\DB\Query;

/**
 * @phpstan-stub
 */
class Builder
{
    public function where(string $column, string $operator, mixed $value): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function whereNull(string $column): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function whereNotNull(string $column): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function get(): array
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function insert(array $data): int
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function update(array $data): int
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function delete(): int
    {
        throw new \BadMethodCallException('Stub only');
    }
}

namespace Marwa\DB\Facades;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Query\Builder;

/**
 * @phpstan-stub
 */
class DB
{
    private static ?ConnectionManager $manager = null;

    public static function setManager(ConnectionManager $manager): void
    {
        self::$manager = $manager;
    }

    public static function connection(string $name = 'default'): ConnectionManager
    {
        if (self::$manager === null) {
            throw new \RuntimeException('Database manager not initialized');
        }
        return self::$manager;
    }

    public static function table(string $table, string $connection = 'default'): Builder
    {
        throw new \BadMethodCallException('Stub only');
    }
}

namespace Marwa\Configuration;

class Config
{
    public function get(string $key, mixed $default = null): mixed
    {
        throw new \BadMethodCallException('Stub only');
    }
}