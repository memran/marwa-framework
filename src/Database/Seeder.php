<?php

declare(strict_types=1);

namespace Marwa\Framework\Database;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Marwa\DB\Facades\DB;
use Marwa\DB\Query\Builder;
use Marwa\DB\Seeder\AbstractSeeder;

abstract class Seeder extends AbstractSeeder
{
    private ?Generator $faker = null;

    protected function faker(?string $locale = null): Generator
    {
        if ($this->faker instanceof Generator) {
            return $this->faker;
        }

        if (!class_exists(FakerFactory::class)) {
            throw new \RuntimeException('fakerphp/faker is required to use framework seeders.');
        }

        $this->faker = FakerFactory::create($locale ?? (string) env('FAKER_LOCALE', 'en_US'));

        return $this->faker;
    }

    protected function table(string $table, string $connection = 'default'): Builder
    {
        return DB::table($table, $connection);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function insertMany(string $table, array $rows, string $connection = 'default'): int
    {
        $inserted = 0;

        foreach ($rows as $row) {
            $this->table($table, $connection)->insert($row);
            $inserted++;
        }

        return $inserted;
    }

    protected function truncate(string $table, string $connection = 'default'): int
    {
        return $this->table($table, $connection)->delete();
    }
}
