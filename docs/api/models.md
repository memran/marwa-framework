# Model API

## `Marwa\Framework\Database\Model`

Framework base model built on top of `marwa-db`.

### Static helpers

- `newQuery(): QueryBuilder`
- `tableName(): string`
- `useConnection(string $connection): void`
- `newInstance(array $attributes = [], bool $exists = false): static`
- `paginate(int $perPage = 15, int $page = 1): array`
- `firstWhere(string $column, mixed $value, string $operator = '='): ?static`
- `findBy(string $column, mixed $value): ?static`
- `firstOrCreate(array $attributes, array $values = []): static`
- `updateOrCreate(array $attributes, array $values = []): static`

### Instance helpers

- `exists(): bool`
- `forceFill(array $attributes): static`
- `syncOriginal(): static`
- `isDirty(?string $key = null): bool`
- `isClean(?string $key = null): bool`
- `fresh(): ?static`
- `saveOrFail(): static`
- `deleteOrFail(): static`

## Example

```php
use Marwa\Framework\Database\Model;

final class User extends Model
{
    protected static ?string $table = 'users';
    protected static array $fillable = ['name', 'email'];
}
```
