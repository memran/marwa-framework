<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Facades\DB;
use Marwa\DB\Schema\Schema;
use Marwa\Framework\Application;
use Marwa\Framework\Database\Seeder as FrameworkSeeder;
use Marwa\Framework\Tests\Fixtures\Seeders\DatabaseSeeder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SeederSupportTest extends TestCase
{
    private string $basePath;
    private string $databaseFile;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->handlersBooted = false;
        $this->basePath = sys_get_temp_dir() . '/marwa-seeder-' . bin2hex(random_bytes(6));
        $this->databaseFile = $this->basePath . '/database/database.sqlite';
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_NAME=\"Seeder App\"\nAPP_VERSION=1.0.0\nTIMEZONE=UTC\nFAKER_LOCALE=en_US\n");
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/database.php',
            $this->basePath . '/config/console.php',
            $this->basePath . '/.env',
            $this->databaseFile,
        ] as $file) {
            @unlink($file);
        }

        $this->removeDirectory($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['APP_NAME'],
            $_ENV['APP_VERSION'],
            $_ENV['TIMEZONE'],
            $_ENV['FAKER_LOCALE'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_NAME'],
            $_SERVER['APP_VERSION'],
            $_SERVER['TIMEZONE'],
            $_SERVER['FAKER_LOCALE']
        );

        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testFrameworkSeederProvidesFakerAndBulkInsertHelpers(): void
    {
        $app = $this->bootDatabaseApplication();

        Schema::create('users', static function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
        });

        $seeder = new class () extends FrameworkSeeder {
            public function run(): void
            {
                $faker = $this->faker();
                $this->truncate('users');

                $this->insertMany('users', [
                    ['name' => $faker->name(), 'email' => $faker->unique()->safeEmail()],
                    ['name' => $faker->name(), 'email' => $faker->unique()->safeEmail()],
                ]);
            }
        };

        $seeder->run();

        self::assertSame(2, DB::table('users')->count());
    }

    public function testDbSeedCommandRunsExplicitSeederClass(): void
    {
        $app = $this->bootDatabaseApplication(
            __DIR__ . '/Fixtures/Seeders',
            'Marwa\\Framework\\Tests\\Fixtures\\Seeders'
        );
        $console = $app->console()->application();
        $this->handlersBooted = true;

        Schema::create('users', static function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
        });

        $command = $console->find('db:seed');
        $tester = new CommandTester($command);
        $status = $tester->execute([
            'class' => DatabaseSeeder::class,
        ]);

        self::assertSame(0, $status);
        self::assertSame(5, DB::table('users')->count());
    }

    public function testMakeSeederCommandGeneratesFrameworkSeederStub(): void
    {
        $app = $this->bootDatabaseApplication();
        $console = $app->console()->application();
        $this->handlersBooted = true;

        $command = $console->find('make:seeder');
        $tester = new CommandTester($command);
        $status = $tester->execute([
            'name' => 'UserSeeder',
        ]);

        self::assertSame(0, $status);

        $path = $this->basePath . '/database/seeders/UserSeeder.php';
        self::assertFileExists($path);

        $contents = (string) file_get_contents($path);
        self::assertStringContainsString('namespace Database\\Seeders;', $contents);
        self::assertStringContainsString('use Marwa\\Framework\\Database\\Seeder;', $contents);
        self::assertStringContainsString('extends Seeder', $contents);
        self::assertStringContainsString('$this->faker()', $contents);
    }

    private function bootDatabaseApplication(
        ?string $seedersPath = null,
        ?string $seedersNamespace = null
    ): Application {
        $seedersPath ??= $this->basePath . '/database/seeders';
        $seedersNamespace ??= 'Database\\Seeders';

        if (!is_dir($seedersPath)) {
            mkdir($seedersPath, 0777, true);
        }

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
    'seedersPath' => '{$seedersPath}',
    'seedersNamespace' => '{$seedersNamespace}',
];
PHP
        );

        $app = new Application($this->basePath);
        $app->console()->application();
        $this->handlersBooted = true;

        return $app;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
