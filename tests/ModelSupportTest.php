<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Tests\Fixtures\Models\CrudUser;
use PHPUnit\Framework\TestCase;

final class ModelSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-models-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents(
            $this->basePath . '/config/database.php',
            "<?php\n\nreturn [\n    'enabled' => true,\n    'default' => 'sqlite',\n    'connections' => [\n        'sqlite' => [\n            'driver' => 'sqlite',\n            'database' => '" . $this->basePath . "/database/database.sqlite',\n            'debug' => false,\n        ],\n    ],\n];\n"
        );
    }

    protected function tearDown(): void
    {
        @restore_error_handler();
        @restore_exception_handler();

        foreach ([
            $this->basePath . '/config/database.php',
            $this->basePath . '/.env',
            $this->basePath . '/database/database.sqlite',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/database');
        @rmdir($this->basePath);
        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testFrameworkModelAddsCrudHelpers(): void
    {
        $app = new \Marwa\Framework\Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $manager */
        $manager = $app->make(ConnectionManager::class);
        $manager->getPdo()->exec(<<<'SQL'
CREATE TABLE crud_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    active INTEGER NOT NULL DEFAULT 0,
    meta TEXT NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL
)
SQL);

        $user = CrudUser::create([
            'name' => 'Alice',
            'email' => 'alice@example.test',
            'active' => true,
            'meta' => ['role' => 'admin'],
        ]);

        self::assertTrue($user->exists());
        self::assertSame('crud_users', CrudUser::tableName());
        self::assertSame(['role' => 'admin'], $user->toArray()['meta']);
        self::assertTrue($user->toArray()['active']);
        self::assertInstanceOf(CrudUser::class, CrudUser::findBy('email', 'alice@example.test'));

        $existing = CrudUser::firstOrCreate(
            ['email' => 'alice@example.test'],
            ['name' => 'Ignored', 'active' => false]
        );

        self::assertSame($user->getKey(), $existing->getKey());
        self::assertSame('Alice', $existing->getAttribute('name'));

        $updated = CrudUser::updateOrCreate(
            ['email' => 'alice@example.test'],
            ['name' => 'Alice Updated']
        );

        self::assertSame('Alice Updated', $updated->getAttribute('name'));
        self::assertFalse($updated->isDirty());
        self::assertTrue($updated->isClean());

        $updated->forceFill(['name' => 'Alice Forced']);
        self::assertTrue($updated->isDirty('name'));
        $updated->saveOrFail();

        self::assertSame('Alice Forced', CrudUser::findBy('email', 'alice@example.test')->getAttribute('name'));
        self::assertInstanceOf(CrudUser::class, $updated->fresh());

        CrudUser::create([
            'name' => 'Bob',
            'email' => 'bob@example.test',
            'active' => false,
        ]);

        $page = CrudUser::paginate(1, 2);

        self::assertSame(2, $page['total']);
        self::assertSame(1, $page['per_page']);
        self::assertSame(2, $page['current_page']);
        self::assertCount(1, $page['data']);
        self::assertInstanceOf(CrudUser::class, $page['data'][0]);
    }
}
