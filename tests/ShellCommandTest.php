<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Console\Commands\ShellCommand;
use Marwa\Framework\Contracts\ShellFactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ShellCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-shell-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        $this->removeDirectory($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testShellCommandPromptsForPsyshWhenUnavailable(): void
    {
        $command = new ShellCommand(new class () implements ShellFactoryInterface {
            public function available(): bool
            {
                return false;
            }

            public function run(Application $app, array $variables = []): int
            {
                return 1;
            }
        });

        $tester = new CommandTester($command);
        self::assertSame(1, $tester->execute([]));
        self::assertStringContainsString('PsySH is not installed', $tester->getDisplay());
    }

    public function testShellCommandPassesFrameworkContextToShellFactory(): void
    {
        $app = new Application($this->basePath);
        $factory = new class () implements ShellFactoryInterface {
            /**
             * @var array<string, mixed>
             */
            public array $captured = [];

            public function available(): bool
            {
                return true;
            }

            public function run(Application $app, array $variables = []): int
            {
                $this->captured = $variables;

                return 0;
            }
        };

        $command = new ShellCommand($factory);

        $command->setMarwaApplication($app);

        $tester = new CommandTester($command);
        self::assertSame(0, $tester->execute([]));
        self::assertArrayHasKey('app', $factory->captured);
        self::assertArrayHasKey('container', $factory->captured);
        self::assertArrayHasKey('config', $factory->captured);
        self::assertArrayHasKey('logger', $factory->captured);
        self::assertSame($app, $factory->captured['app']);
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
