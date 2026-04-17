<?php

declare(strict_types=1);

namespace Marwa\Framework\Database;

use Marwa\DB\ORM\Model as BaseModel;
use Marwa\DB\ORM\QueryBuilder;
use PDO;

/**
 * Framework model base built on top of marwa-db ORM.
 *
 * This layer keeps the upstream persistence engine intact while adding
 * application-friendly CRUD helpers and model lifecycle convenience methods.
 */
abstract class Model extends BaseModel
{
    public static function newQuery(): QueryBuilder
    {
        return static::query();
    }

    public static function tableName(): string
    {
        return static::table();
    }

    public static function useConnection(string $connection): void
    {
        if ($connection !== '') {
            static::$connection = $connection;
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function newInstance(array $attributes = [], bool $exists = false): static
    {
        return new static(static::normalizeAttributes($attributes), $exists);
    }

    /**
     * @return array{
     *     data: array<int, static>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int
     * }
     */
    public static function paginate(int $perPage = 15, int $page = 1): array
    {
        $pageData = static::applySoftDeleteFilter(static::baseQuery())->paginate($perPage, $page);

        $pageData['data'] = array_map(
            static fn (array|object $row): static => new static(
                static::normalizeAttributes((array) $row),
                true
            ),
            $pageData['data']
        );

        return $pageData;
    }

    public static function firstWhere(string $column, mixed $value, string $operator = '='): ?static
    {
        $row = static::applySoftDeleteFilter(static::baseQuery())
            ->where($column, $operator, $value)
            ->first(PDO::FETCH_ASSOC);

        return $row === null ? null : new static(static::normalizeAttributes($row), true);
    }

    public static function findBy(string $column, mixed $value): ?static
    {
        return static::firstWhere($column, $value);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     */
    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $model = static::findFirstByAttributes($attributes);
        if ($model instanceof static) {
            return $model;
        }

        return static::create(array_replace($attributes, $values));
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     */
    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $model = static::findFirstByAttributes($attributes);
        if ($model instanceof static) {
            $model->fill($values);
            $model->save();

            return $model;
        }

        return static::create(array_replace($attributes, $values));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function create(array $attributes): static
    {
        return parent::create(static::normalizeAttributes($attributes));
    }

    public function exists(): bool
    {
        return $this->getKey() !== null;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function forceFill(array $attributes): static
    {
        $this->attributes = array_replace($this->attributes, static::normalizeAttributes($attributes));

        return $this;
    }

    public function syncOriginal(): static
    {
        $this->original = $this->attributes;

        return $this;
    }

    public function isDirty(?string $key = null): bool
    {
        if ($key !== null) {
            return ($this->attributes[$key] ?? null) !== ($this->original[$key] ?? null);
        }

        return $this->getDirty() !== [];
    }

    public function isClean(?string $key = null): bool
    {
        return !$this->isDirty($key);
    }

    public function fresh(): ?static
    {
        $key = $this->getKey();

        return $key === null ? null : static::find($key);
    }

    public function saveOrFail(): static
    {
        if (!$this->save()) {
            throw new \RuntimeException(sprintf('Unable to save model [%s].', static::class));
        }

        return $this;
    }

    public function deleteOrFail(): static
    {
        if (!$this->delete()) {
            throw new \RuntimeException(sprintf('Unable to delete model [%s].', static::class));
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected static function findFirstByAttributes(array $attributes): ?static
    {
        $query = static::applySoftDeleteFilter(static::baseQuery());

        foreach ($attributes as $column => $value) {
            $query->where($column, '=', $value);
        }

        $row = $query->first(PDO::FETCH_ASSOC);

        return $row === null ? null : new static(static::normalizeAttributes($row), true);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    protected static function normalizeAttributes(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            $attributes[$key] = static::normalizeAttribute($key, $value);
        }

        return $attributes;
    }

    protected static function normalizeAttribute(string $key, mixed $value): mixed
    {
        $cast = static::$casts[$key] ?? null;

        if ($value === null) {
            return null;
        }

        return match ($cast) {
            'json', 'array' => is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR),
            'bool' => (bool) $value,
            'int' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };
    }
}
