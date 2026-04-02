<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use League\Container\Container;
use Marwa\Framework\Application;
use Marwa\Framework\Config\ConsoleConfig;
use Marwa\Framework\Console\CommandRegistry;
use Marwa\Framework\Console\Commands\MakeAiHelperCommand;
use Marwa\Framework\Console\Commands\MakeCommandCommand;
use Marwa\Framework\Console\Commands\MakeControllerCommand;
use Marwa\Framework\Console\Commands\MakeModelCommand;
use Marwa\Framework\Console\Commands\MakeModuleCommand;
use Marwa\Framework\Tests\Fixtures\Console\Commands\DemoCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ConsoleKernelTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->handlersBooted = false;
        $this->basePath = sys_get_temp_dir() . '/marwa-console-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_NAME=\"Console App\"\nAPP_VERSION=1.2.3\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/console.php',
            $this->basePath . '/config/module.php',
            $this->basePath . '/config/database.php',
            $this->basePath . '/.env',
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
            $_SERVER['APP_ENV'],
            $_SERVER['APP_NAME'],
            $_SERVER['APP_VERSION'],
            $_SERVER['TIMEZONE']
        );
        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testApplicationBindsTheSharedLeagueContainerInstance(): void
    {
        $app = new Application($this->basePath);

        self::assertSame($app->container(), $app->make(Container::class));
    }

    public function testConsoleKernelRegistersConfiguredAndDiscoveredCommands(): void
    {
        $fixturePath = __DIR__ . '/Fixtures/Console/Commands';

        file_put_contents(
            $this->basePath . '/config/console.php',
            <<<PHP
<?php

use Marwa\\Framework\\Tests\\Fixtures\\Console\\Commands\\DemoCommand;

return [
    'commands' => [
        DemoCommand::class,
    ],
    'discover' => [
        [
            'namespace' => 'Marwa\\\\Framework\\\\Tests\\\\Fixtures\\\\Console\\\\Commands',
            'path' => '{$fixturePath}',
        ],
    ],
];
PHP
        );

        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        self::assertTrue($console->has('demo:run'));
        self::assertTrue($console->has('bootstrap:cache'));
        self::assertTrue($console->has('config:cache'));
        self::assertTrue($console->has('route:cache'));
        self::assertTrue($console->has('module:cache'));
        self::assertTrue($console->has('make:command'));
        self::assertTrue($console->has('make:controller'));
        self::assertTrue($console->has('make:model'));
        self::assertTrue($console->has('make:module'));
        self::assertTrue($console->has('make:ai-helper'));
        self::assertSame('Console App', $console->getName());
        self::assertSame('1.2.3', $console->getVersion());
    }

    public function testApplicationAllowsProgrammaticCommandRegistration(): void
    {
        $app = new Application($this->basePath);

        $app->registerCommand(DemoCommand::class);

        $commands = $app->make(CommandRegistry::class)->resolve();

        self::assertCount(1, array_filter($commands, static fn ($command): bool => $command instanceof DemoCommand));
    }

    public function testConsoleConfigDefaultsIncludeAiStubCommand(): void
    {
        $app = new Application($this->basePath);

        self::assertContains(MakeCommandCommand::class, ConsoleConfig::defaults($app)['commands']);
        self::assertContains(MakeControllerCommand::class, ConsoleConfig::defaults($app)['commands']);
        self::assertContains(MakeModelCommand::class, ConsoleConfig::defaults($app)['commands']);
        self::assertContains(MakeModuleCommand::class, ConsoleConfig::defaults($app)['commands']);
        self::assertContains(MakeAiHelperCommand::class, ConsoleConfig::defaults($app)['commands']);
    }

    public function testConsoleKernelAutoDiscoversModuleCommands(): void
    {
        $moduleFixture = __DIR__ . '/Fixtures/Modules';

        file_put_contents(
            $this->basePath . '/config/module.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'paths' => ['{$moduleFixture}'],
];
PHP
        );

        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        self::assertTrue($console->has('blog:hello'));
    }

    public function testConsoleKernelRegistersMarwaDbCommandsWhenDatabaseIsEnabled(): void
    {
        file_put_contents(
            $this->basePath . '/config/database.php',
            <<<'PHP'
<?php

return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
    ],
];
PHP
        );

        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        self::assertTrue($console->has('migrate'));
        self::assertTrue($console->has('migrate:rollback'));
        self::assertTrue($console->has('migrate:refresh'));
        self::assertTrue($console->has('migrate:status'));
        self::assertTrue($console->has('make:migration'));
        self::assertTrue($console->has('make:seeder'));
        self::assertTrue($console->has('db:seed'));
    }

    public function testMakeControllerCommandCreatesNestedResourceController(): void
    {
        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        $command = $console->find('make:controller');
        $tester = new CommandTester($command);
        $status = $tester->execute([
            'name' => 'Admin/Post',
            '--resource' => true,
        ]);

        self::assertSame(0, $status);

        $path = $this->basePath . '/app/Http/Controllers/Admin/PostController.php';
        self::assertFileExists($path);

        $contents = (string) file_get_contents($path);

        self::assertStringContainsString('namespace App\\Http\\Controllers\\Admin;', $contents);
        self::assertStringContainsString('final class PostController', $contents);
        self::assertStringContainsString('public function index(): ResponseInterface', $contents);
    }

    public function testMakeModelCommandCreatesModelAndMatchingMigration(): void
    {
        file_put_contents(
            $this->basePath . '/config/database.php',
            <<<'PHP'
<?php

return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
    ],
];
PHP
        );

        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        $command = $console->find('make:model');
        $tester = new CommandTester($command);
        $status = $tester->execute([
            'name' => 'Billing/Category',
            '--migration' => true,
        ]);

        self::assertSame(0, $status);

        $modelPath = $this->basePath . '/app/Models/Billing/Category.php';
        self::assertFileExists($modelPath);

        $model = (string) file_get_contents($modelPath);
        self::assertStringContainsString('namespace App\\Models\\Billing;', $model);
        self::assertStringContainsString("protected static ?string \$table = 'categories';", $model);

        $migrations = glob($this->basePath . '/database/migrations/*_create_categories_table.php');

        self::assertIsArray($migrations);
        self::assertCount(1, $migrations);
    }

    public function testMakeModuleCommandCreatesModuleScaffoldInConfiguredModulesPath(): void
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

        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        $command = $console->find('make:module');
        $tester = new CommandTester($command);
        $status = $tester->execute([
            'name' => 'Blog',
        ]);

        self::assertSame(0, $status);

        $modulePath = $this->basePath . '/modules/Blog';
        self::assertFileExists($modulePath . '/manifest.php');
        self::assertFileExists($modulePath . '/BlogServiceProvider.php');
        self::assertFileExists($modulePath . '/routes/http.php');
        self::assertFileExists($modulePath . '/resources/views/index.twig');
        self::assertDirectoryExists($modulePath . '/Console/Commands');

        $manifest = (string) file_get_contents($modulePath . '/manifest.php');
        self::assertStringContainsString("'slug' => 'blog'", $manifest);
        self::assertStringContainsString("App\\Modules\\Blog\\BlogServiceProvider::class", $manifest);
        self::assertStringContainsString("'commands' => 'Console/Commands'", $manifest);
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
