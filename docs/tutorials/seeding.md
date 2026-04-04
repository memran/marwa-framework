# Seeding

The framework supports Faker-backed seeders through `marwa-db` and the framework base seeder at `Marwa\Framework\Database\Seeder`.

## Generate a Seeder

```bash
php bin/console make:seeder UserSeeder
```

The generated seeder extends the framework base class and gives you `faker()`, `insertMany()`, and `truncate()` helpers.

## Run Seeders

```bash
php bin/console db:seed
php bin/console db:seed --class=DatabaseSeeder
php bin/console db:seed --dry-run
```

`DatabaseSeeder` is a good entry point when you want one file to orchestrate multiple seeders.

## Example

```php
use Marwa\Framework\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = $this->faker();

        $this->truncate('users');

        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = [
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
            ];
        }

        $this->insertMany('users', $rows);
    }
}
```

Use the configured `seedersPath` and `seedersNamespace` in `config/database.php` to control where seeders live and how they are discovered.
