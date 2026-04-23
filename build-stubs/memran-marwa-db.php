<?php

declare(strict_types=1);

namespace Memran\MarwaDb;

use Memran\MarwaDb\Schema\Blueprint;

/**
 * @phpstan-stub This is a stub for type checking only
 */
class Database
{
    /**
     * @return \Memran\MarwaDb\QueryBuilder
     */
    public static function connection(string $name): self
    {
        return new self();
    }

    /**
     * @return \Memran\MarwaDb\QueryBuilder
     */
    public function table(string $name)
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @return \Memran\MarwaDb\Schema\SchemaBuilder
     */
    public function schema()
    {
        throw new \BadMethodCallException('Stub only');
    }
}

namespace Memran\MarwaDb\Schema;

/**
 * @phpstan-stub This is a stub for type checking only
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

    public function dateTime(string $name): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function timestamps(): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function primary(): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function default(mixed $value): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    public function nullable(): self
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

    /**
     * @param list<string> $columns
     */
    public function unique(array $columns, ?string $name = null): self
    {
        throw new \BadMethodCallException('Stub only');
    }
}

/**
 * @phpstan-stub This is a stub for type checking only
 */
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

/**
 * @phpstan-stub This is a stub for type checking only
 */
#[\PHPStan\ShouldNotBePublic]
class QueryBuilder
{
    /**
     * @return $this
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @return $this
     */
    public function whereNull(string $column): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @return $this
     */
    public function whereNotNull(string $column): self
    {
        throw new \BadMethodCallException('Stub only');
    }

    /**
     * @return $this
     */
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