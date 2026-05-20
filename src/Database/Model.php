<?php

declare(strict_types=1);

namespace Marwa\Framework\Database;

use Marwa\DB\ORM\Model as BaseModel;
use Marwa\DB\ORM\QueryBuilder;
use Marwa\DB\Support\Helpers;

/**
 * Framework model base built on top of marwa-db ORM.
 *
 * This layer keeps the upstream persistence engine intact while adding
 * application-friendly convenience aliases, framework-specific cast
 * normalization, and audit lifecycle events for soft-delete operations.
 */
abstract class Model extends BaseModel
{
    /**
     * @var array<string, list<callable>>
     */
    protected static array $auditObservers = [];

    // ----------------------------------------------------------------
    //  Convenience aliases
    // ----------------------------------------------------------------

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

    public static function findBy(string $column, mixed $value): ?static
    {
        return static::firstWhere($column, $value);
    }

    // ----------------------------------------------------------------
    //  Instance convenience
    // ----------------------------------------------------------------

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

    // ----------------------------------------------------------------
    //  Framework-specific overrides (casts + audit)
    // ----------------------------------------------------------------

    /**
     * @param array<string, mixed> $attributes
     */
    public static function create(array $attributes): static
    {
        return parent::create(static::normalizeAttributes($attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function forceFill(array $attributes): static
    {
        return parent::forceFill(static::normalizeAttributes($attributes));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $attributes = parent::toArray();

        foreach (static::$casts as $key => $cast) {
            if (!isset($attributes[$key]) || !in_array($cast, ['json', 'array'], true)) {
                continue;
            }

            $attributes[$key] = static::normalizeCastOutput($attributes[$key]);
        }

        return $attributes;
    }

    // ----------------------------------------------------------------
    //  Hydration hook (ensures normalizeAttributes runs on every
    //  row hydration path used by the upstream traits)
    // ----------------------------------------------------------------

    /**
     * @param array<string, mixed>|object $row
     */
    protected static function hydrateRow(array|object $row): static
    {
        $data = is_array($row) ? $row : (array) $row;

        return new static(static::normalizeAttributes($data), true);
    }

    // ----------------------------------------------------------------
    //  Soft-delete / audit lifecycle
    // ----------------------------------------------------------------

    public function restore(): bool
    {
        if (!static::$softDeletes) {
            return false;
        }

        static::fireAuditEvent('restoring', $this);

        $result = parent::restore();

        if ($result) {
            static::fireAuditEvent('restored', $this);
        }

        return $result;
    }

    public function forceDelete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        static::fireAuditEvent('forceDeleting', $this);

        $result = parent::forceDelete();

        if ($result) {
            static::fireEvent('deleted', $this);
            static::fireAuditEvent('forceDeleted', $this);
        }

        return $result;
    }

    public static function destroy(int|array $ids): int
    {
        $ids = array_values(array_unique(array_map(
            static fn (int|string $id): int => (int) $id,
            is_array($ids) ? $ids : [$ids]
        ), SORT_REGULAR));

        if ($ids === []) {
            return 0;
        }

        $instance = new static();
        $primaryKey = $instance->getPrimaryKey();
        $rows = static::baseQuery()
            ->whereIn($primaryKey, $ids)
            ->get();

        if ($rows === []) {
            return 0;
        }

        $models = array_map(
            static fn (array|object $row): static => static::hydrateRow($row),
            $rows
        );

        foreach ($models as $model) {
            static::fireAuditEvent('destroying', $model);
        }

        $affected = 0;

        if (static::$softDeletes) {
            $deletedAt = Helpers::now();
            $affected = static::baseQuery()
                ->whereIn($primaryKey, array_map(static fn ($model) => $model->getKey(), $models))
                ->update(['deleted_at' => $deletedAt]);

            if ($affected > 0) {
                foreach ($models as $model) {
                    $model->attributes['deleted_at'] = $deletedAt;
                    $model->original['deleted_at'] = $deletedAt;
                    static::fireAuditEvent('destroyed', $model);
                }
            }

            return $affected;
        }

        $affected = static::baseQuery()
            ->whereIn($primaryKey, array_map(static fn ($model) => $model->getKey(), $models))
            ->delete();

        if ($affected > 0) {
            foreach ($models as $model) {
                $model->exists = false;
                static::fireAuditEvent('destroyed', $model);
            }
        }

        return $affected;
    }

    // ----------------------------------------------------------------
    //  Audit event registration
    // ----------------------------------------------------------------

    public static function onRestoring(callable $callback): void
    {
        static::observeAudit('restoring', $callback);
    }

    public static function onRestored(callable $callback): void
    {
        static::observeAudit('restored', $callback);
    }

    public static function onForceDeleting(callable $callback): void
    {
        static::observeAudit('forceDeleting', $callback);
    }

    public static function onForceDeleted(callable $callback): void
    {
        static::observeAudit('forceDeleted', $callback);
    }

    public static function onDestroying(callable $callback): void
    {
        static::observeAudit('destroying', $callback);
    }

    public static function onDestroyed(callable $callback): void
    {
        static::observeAudit('destroyed', $callback);
    }

    public static function flushAuditObservers(): void
    {
        static::$auditObservers = [];
    }

    // ----------------------------------------------------------------
    //  Cast normalization
    // ----------------------------------------------------------------

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

    /**
     * @return array<mixed>|scalar|null
     */
    protected static function normalizeCastOutput(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([static::class, 'normalizeCastOutput'], $value);
        }

        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

                return is_array($decoded)
                    ? array_map([static::class, 'normalizeCastOutput'], $decoded)
                    : $decoded;
            } catch (\JsonException) {
                return $value;
            }
        }

        if (is_object($value)) {
            $decoded = json_decode(
                json_encode($value, JSON_THROW_ON_ERROR) ?: 'null',
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return is_array($decoded)
                ? array_map([static::class, 'normalizeCastOutput'], $decoded)
                : $decoded;
        }

        return $value;
    }

    // ----------------------------------------------------------------
    //  Audit infrastructure
    // ----------------------------------------------------------------

    protected static function observeAudit(string $event, callable $callback): void
    {
        static::$auditObservers[$event][] = $callback;
    }

    protected static function fireAuditEvent(string $event, BaseModel $model): void
    {
        foreach (static::$auditObservers[$event] ?? [] as $callback) {
            $callback($model);
        }
    }
}
