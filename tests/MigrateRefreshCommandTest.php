<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Tester\CommandTester;

final class MigrateRefreshCommandTest extends TestCase
{
    private string $basePath;
    private string $databaseFile;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->handlersBooted = false;
        $this->basePath = sys_get_temp_dir() . '/marwa-migrate-refresh-' . bin2hex(random_bytes(6));
        $this->databaseFile = $this->basePath . '/database/database.sqlite';
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );

        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testRefreshRunsApplicationAndModuleMigrationsFromScratch(): void
    {
        $this->writeDatabaseConfig();
        $this->writeModuleConfig();
        $this->writeApplicationMigration('2025_01_01_000000_create_application_settings_table', 'application_settings');
        $this->writeModule('Alpha', 'alpha', [
            '2025_12_31_000000_create_alpha_items_table' => 'alpha_items',
            '2025_12_31_000100_create_alpha_notes_table' => 'alpha_notes',
        ]);

        $tester = new CommandTester($this->console()->find('migrate:refresh'));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString(
            'Refreshed application and module migrations. Down: 0, Up: 3',
            $tester->getDisplay(),
        );

        $tables = $this->tables();
        self::assertContains('application_settings', $tables);
        self::assertContains('alpha_items', $tables);
        self::assertContains('alpha_notes', $tables);
        self::assertSame([
            '2025_01_01_000000_create_application_settings_table',
            '2025_12_31_000000_create_alpha_items_table',
            '2025_12_31_000100_create_alpha_notes_table',
        ], $this->ranMigrations());
    }

    public function testRefreshRollsBackEveryBatchAndRecreatesSchema(): void
    {
        $this->writeDatabaseConfig();
        $this->writeModuleConfig();
        $this->writeApplicationMigration('2025_01_01_000000_create_application_settings_table', 'application_settings');
        $this->writeModule('Alpha', 'alpha', [
            '2025_12_31_000000_create_alpha_items_table' => 'alpha_items',
            '2025_12_31_000100_create_alpha_notes_table' => 'alpha_notes',
        ]);

        $console = $this->console();
        self::assertSame(0, (new CommandTester($console->find('migrate')))->execute([]));
        self::assertSame(0, (new CommandTester($console->find('module:migrate')))->execute([]));

        self::assertGreaterThanOrEqual(
            2,
            (int) $this->pdo()->query('SELECT COUNT(DISTINCT batch) FROM migrations')->fetchColumn(),
            'Application and module migrations must land in separate batches.',
        );

        $this->pdo()->exec("INSERT INTO application_settings (name) VALUES ('seed')");
        self::assertSame(1, (int) $this->pdo()->query('SELECT COUNT(*) FROM application_settings')->fetchColumn());

        $tester = new CommandTester($console->find('migrate:refresh'));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString(
            'Refreshed application and module migrations. Down: 3, Up: 3',
            $tester->getDisplay(),
        );

        self::assertSame(0, (int) $this->pdo()->query('SELECT COUNT(*) FROM application_settings')->fetchColumn());
        self::assertContains('application_settings', $this->tables());
        self::assertContains('alpha_items', $this->tables());
        self::assertContains('alpha_notes', $this->tables());
        self::assertSame([
            '2025_01_01_000000_create_application_settings_table',
            '2025_12_31_000000_create_alpha_items_table',
            '2025_12_31_000100_create_alpha_notes_table',
        ], $this->ranMigrations());
    }

    public function testRefreshWithoutModuleConfigurationRunsApplicationMigrationsOnly(): void
    {
        $this->writeDatabaseConfig();
        $this->writeApplicationMigration('2025_01_01_000000_create_application_settings_table', 'application_settings');

        $tester = new CommandTester($this->console()->find('migrate:refresh'));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString(
            'Refreshed application and module migrations. Down: 0, Up: 1',
            $tester->getDisplay(),
        );
        self::assertContains('application_settings', $this->tables());
    }

    public function testRefreshAbortsBeforeRollbackWhenAMigrationFileIsMissing(): void
    {
        $this->writeDatabaseConfig();
        $this->writeModuleConfig();
        $this->writeApplicationMigration('2025_01_01_000000_create_application_settings_table', 'application_settings');
        $this->writeModule('Alpha', 'alpha', [
            '2025_12_31_000000_create_alpha_items_table' => 'alpha_items',
        ]);

        $console = $this->console();
        $tester = new CommandTester($console->find('migrate:refresh'));

        self::assertSame(0, $tester->execute([]));

        $ranBefore = $this->ranMigrations();
        unlink($this->basePath . '/database/migrations/2025_01_01_000000_create_application_settings_table.php');

        try {
            $tester->execute([]);
            self::fail('Refresh should fail before rollback when a migration file is missing.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'Cannot refresh because migration [2025_01_01_000000_create_application_settings_table] is no longer available.',
                $exception->getMessage(),
            );
        }

        self::assertSame($ranBefore, $this->ranMigrations());
        self::assertContains('application_settings', $this->tables());
        self::assertContains('alpha_items', $this->tables());
    }

    public function testRefreshFailsWhenMigrationNameIsDuplicatedAcrossDirectories(): void
    {
        $this->writeDatabaseConfig();
        $this->writeModuleConfig();
        $this->writeApplicationMigration('2025_01_01_000000_create_shared_table', 'shared_app');
        $this->writeModule('Alpha', 'alpha', [
            '2025_01_01_000000_create_shared_table' => 'shared_module',
        ]);

        $tester = new CommandTester($this->console()->find('migrate:refresh'));

        try {
            $tester->execute([]);
            self::fail('Refresh should fail when a migration name exists in multiple directories.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'Duplicate migration name [2025_01_01_000000_create_shared_table] exists in multiple migration directories.',
                $exception->getMessage(),
            );
        }

        self::assertNotContains('shared_app', $this->tables());
        self::assertNotContains('shared_module', $this->tables());
    }

    public function testRefreshIsANoOpWhenMigrationDirectoriesDoNotExist(): void
    {
        $this->writeDatabaseConfig();

        $tester = new CommandTester($this->console()->find('migrate:refresh'));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString(
            'Refreshed application and module migrations. Down: 0, Up: 0',
            $tester->getDisplay(),
        );
        self::assertContains('migrations', $this->tables());
    }

    private function console(): ConsoleApplication
    {
        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        return $console;
    }

    private function writeDatabaseConfig(): void
    {
        file_put_contents(
            $this->basePath . '/config/database.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => '{$this->databaseFile}',
        ],
    ],
];
PHP
        );
    }

    private function writeModuleConfig(): void
    {
        file_put_contents(
            $this->basePath . '/config/module.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'paths' => ['{$this->basePath}/modules'],
];
PHP
        );
    }

    private function writeApplicationMigration(string $name, string $table): void
    {
        $directory = $this->basePath . '/database/migrations';

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $this->writeMigration($directory, $name, $table);
    }

    /**
     * @param array<string, string> $migrations Migration name => table name.
     */
    private function writeModule(string $directory, string $slug, array $migrations): void
    {
        $modulePath = $this->basePath . '/modules/' . $directory;
        $migrationPath = $modulePath . '/database/migrations';
        mkdir($migrationPath, 0777, true);

        $manifestMigrations = [];

        foreach ($migrations as $name => $table) {
            $this->writeMigration($migrationPath, $name, $table);
            $manifestMigrations[] = 'database/migrations/' . $name . '.php';
        }

        file_put_contents(
            $modulePath . '/manifest.php',
            "<?php\n\nreturn " . var_export([
                'name' => $directory . ' Module',
                'slug' => $slug,
                'migrations' => $manifestMigrations,
            ], true) . ";\n"
        );
    }

    private function writeMigration(string $directory, string $name, string $table): void
    {
        file_put_contents(
            $directory . '/' . $name . '.php',
            <<<PHP
<?php

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('{$table}', function (\$table): void {
            \$table->increments('id');
            \$table->string('name');
        });
    }

    public function down(): void
    {
        Schema::drop('{$table}');
    }
};
PHP
        );
    }

    private function pdo(): \PDO
    {
        return new \PDO('sqlite:' . $this->databaseFile);
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        $names = $this->pdo()->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(\PDO::FETCH_COLUMN);

        return array_map(static fn (mixed $name): string => (string) $name, $names);
    }

    /**
     * @return list<string>
     */
    private function ranMigrations(): array
    {
        $names = $this->pdo()->query('SELECT migration FROM migrations ORDER BY id ASC')->fetchAll(\PDO::FETCH_COLUMN);

        return array_map(static fn (mixed $name): string => (string) $name, $names);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $current = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($current)) {
                $this->removeDirectory($current);
                continue;
            }

            @unlink($current);
        }

        @rmdir($path);
    }
}
