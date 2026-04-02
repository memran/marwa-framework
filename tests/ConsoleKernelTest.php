<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use League\Container\Container;
use Marwa\Framework\Application;
use Marwa\Framework\Config\ConsoleConfig;
use Marwa\Framework\Console\CommandRegistry;
use Marwa\Framework\Console\Commands\MakeAiHelperCommand;
use Marwa\Framework\Console\Commands\MakeCommandCommand;
use Marwa\Framework\Tests\Fixtures\Console\Commands\DemoCommand;
use PHPUnit\Framework\TestCase;

final class ConsoleKernelTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
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
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

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

        self::assertTrue($console->has('demo:run'));
        self::assertTrue($console->has('bootstrap:cache'));
        self::assertTrue($console->has('config:cache'));
        self::assertTrue($console->has('route:cache'));
        self::assertTrue($console->has('module:cache'));
        self::assertTrue($console->has('make:command'));
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

        self::assertTrue($console->has('blog:hello'));
    }
}
