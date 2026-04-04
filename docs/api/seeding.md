# Seeding API

## `Marwa\Framework\Database\Seeder`

Framework base class for Faker-backed database seeders.

### Helpers

- `faker(?string $locale = null): \Faker\Generator`
- `table(string $table, string $connection = 'default'): \Marwa\DB\Query\Builder`
- `insertMany(string $table, array $rows, string $connection = 'default'): int`
- `truncate(string $table, string $connection = 'default'): int`

### Example

```php
final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = $this->faker();

        $this->insertMany('users', [
            ['name' => $faker->name(), 'email' => $faker->safeEmail()],
        ]);
    }
}
```

## Console Commands

- `make:seeder` generates a framework seeder stub in `database/seeders`
- `db:seed` runs discovered seeders or an explicit class name
