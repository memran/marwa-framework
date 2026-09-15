<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ModuleCacheCommandTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-module-cache-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/modules/Cached', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");

        file_put_contents(
            $this->basePath . '/modules/Cached/manifest.php',
            <<<'PHP'
<?php

return [
    'name' => 'Cached Module',
    'slug' => 'cached',
    'providers' => [],
];
PHP
        );

        $cacheFile = $this->basePath . '/storage/cache/modules.php';
        $bootstrapCacheFile = $this->basePath . '/storage/cache/module-bootstrap.php';

        file_put_contents(
            $this->basePath . '/config/module.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'paths' => ['{$this->basePath}/modules'],
    'cache' => '{$cacheFile}',
    'bootstrapCache' => '{$bootstrapCacheFile}',
];
PHP
        );
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/storage/cache/modules.php',
            $this->basePath . '/storage/cache/module-bootstrap.php',
            $this->basePath . '/modules/Cached/manifest.php',
            $this->basePath . '/config/module.php',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/storage/cache');
        @rmdir($this->basePath . '/storage');
        @rmdir($this->basePath . '/modules/Cached');
        @rmdir($this->basePath . '/modules');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);

        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testModuleCacheCommandBuildsManifestAndCompiledCaches(): void
    {
        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        $tester = new CommandTester($console->find('module:cache'));
        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('Module cache created:', $tester->getDisplay());
        self::assertStringContainsString('Module bootstrap cache created:', $tester->getDisplay());

        self::assertFileExists($this->basePath . '/storage/cache/modules.php');
        self::assertFileExists($this->basePath . '/storage/cache/module-bootstrap.php');
    }
}
