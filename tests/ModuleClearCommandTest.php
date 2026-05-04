<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ModuleClearCommandTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-module-clear-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/config/module.php');
        @unlink($this->basePath . '/.env');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);

        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testModuleClearCommandHandlesNullCachePath(): void
    {
        file_put_contents(
            $this->basePath . '/config/module.php',
            <<<'PHP'
<?php

return [
    'cache' => null,
];
PHP
        );

        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        $tester = new CommandTester($console->find('module:clear'));
        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('Module cache cleared:', $tester->getDisplay());
        self::assertStringContainsString('(not configured)', $tester->getDisplay());
    }
}
