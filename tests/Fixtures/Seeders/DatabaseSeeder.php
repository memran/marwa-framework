<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Seeders;

use Marwa\Framework\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        (new UserSeeder())->run();
    }
}
