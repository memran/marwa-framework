<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Seeders;

use Marwa\Framework\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = $this->faker();
        $this->truncate('users');

        $rows = [];
        for ($i = 0; $i < 5; $i++) {
            $rows[] = [
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
            ];
        }

        $this->insertMany('users', $rows);
    }
}
