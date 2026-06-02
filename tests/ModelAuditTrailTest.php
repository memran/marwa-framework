<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Tests\Fixtures\Models\AuditUser;
use PHPUnit\Framework\TestCase;

final class ModelAuditTrailTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-model-audit-' . bin2hex(random_bytes(6));
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
        AuditUser::flushAuditObservers();
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

    public function testFrameworkModelEmitsAuditLifecycleHooks(): void
    {
        $app = new \Marwa\Framework\Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $manager */
        $manager = $app->make(ConnectionManager::class);
        $manager->getPdo()->exec(<<<'SQL'
CREATE TABLE audit_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    deleted_at TEXT NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL
)
SQL);

        $events = [];

        AuditUser::onRestoring(static function (AuditUser $model) use (&$events): void {
            $events[] = 'restoring:' . $model->getAttribute('email');
        });

        AuditUser::onRestored(static function (AuditUser $model) use (&$events): void {
            $events[] = 'restored:' . $model->getAttribute('email');
        });

        AuditUser::onForceDeleting(static function (AuditUser $model) use (&$events): void {
            $events[] = 'forceDeleting:' . $model->getAttribute('email');
        });

        AuditUser::onForceDeleted(static function (AuditUser $model) use (&$events): void {
            $events[] = 'forceDeleted:' . $model->getAttribute('email');
        });

        AuditUser::onDestroying(static function (AuditUser $model) use (&$events): void {
            $events[] = 'destroying:' . $model->getAttribute('email');
        });

        AuditUser::onDestroyed(static function (AuditUser $model) use (&$events): void {
            $events[] = 'destroyed:' . $model->getAttribute('email');
        });

        $restorable = AuditUser::create([
            'name' => 'Restore Me',
            'email' => 'restore@example.test',
        ]);
        $restorable->delete();

        $trashed = AuditUser::withTrashed()->where('email', '=', 'restore@example.test')->first();
        self::assertInstanceOf(AuditUser::class, $trashed);
        self::assertTrue($trashed->trashed());
        self::assertTrue($trashed->restore());
        self::assertFalse(AuditUser::withTrashed()->where('email', '=', 'restore@example.test')->first()->trashed());

        $forceDeleted = AuditUser::create([
            'name' => 'Force Delete Me',
            'email' => 'force@example.test',
        ]);
        self::assertTrue($forceDeleted->forceDelete());
        self::assertNull(AuditUser::withTrashed()->where('email', '=', 'force@example.test')->first());

        $destroyedOne = AuditUser::create([
            'name' => 'Destroy One',
            'email' => 'destroy-1@example.test',
        ]);
        $destroyedTwo = AuditUser::create([
            'name' => 'Destroy Two',
            'email' => 'destroy-2@example.test',
        ]);

        self::assertSame(2, AuditUser::destroy([$destroyedOne->getKey(), $destroyedTwo->getKey()]));

        $destroyEvents = array_values(array_filter($events, static fn (string $event): bool => str_starts_with($event, 'destroy')));
        $forceEvents = array_values(array_filter($events, static fn (string $event): bool => str_starts_with($event, 'force')));
        $restoreEvents = array_values(array_filter($events, static fn (string $event): bool => str_starts_with($event, 'restor')));

        self::assertSame([
            'restoring:restore@example.test',
            'restored:restore@example.test',
        ], $restoreEvents);

        self::assertSame([
            'forceDeleting:force@example.test',
            'forceDeleted:force@example.test',
        ], $forceEvents);

        sort($destroyEvents);

        self::assertSame([
            'destroyed:destroy-1@example.test',
            'destroyed:destroy-2@example.test',
            'destroying:destroy-1@example.test',
            'destroying:destroy-2@example.test',
        ], $destroyEvents);
    }
}
