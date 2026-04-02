<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->handlersBooted = false;
        $this->basePath = sys_get_temp_dir() . '/marwa-helpers-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/resources/views/themes/default', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);

        file_put_contents($this->basePath . '/.env', "APP_ENV=local\nTIMEZONE=UTC\n");

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
            'database' => ':memory:',
            'debug' => false,
        ],
    ],
];
PHP
        );

        $moduleFixture = __DIR__ . '/Fixtures/Modules';
        file_put_contents(
            $this->basePath . '/config/module.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'paths' => ['{$moduleFixture}'],
    'cache' => '{$this->basePath}/bootstrap/cache/modules.php',
];
PHP
        );
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/database.php',
            $this->basePath . '/config/module.php',
            $this->basePath . '/.env',
            $this->basePath . '/bootstrap/cache/modules.php',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/bootstrap/cache');
        @rmdir($this->basePath . '/bootstrap');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/resources/views/themes/default');
        @rmdir($this->basePath . '/resources/views/themes');
        @rmdir($this->basePath . '/resources/views');
        @rmdir($this->basePath . '/resources');
        @rmdir($this->basePath . '/database');
        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $GLOBALS['marwa_module_routes'],
            $GLOBALS['cm'],
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

    public function testPathHelpersResolveFrameworkDirectories(): void
    {
        new Application($this->basePath);

        self::assertSame($this->basePath, base_path());
        self::assertSame($this->basePath . '/database/migrations', database_path('migrations'));
        self::assertSame($this->basePath . '/public/build', public_path('build'));
        self::assertSame($this->basePath . '/bootstrap/cache', bootstrap_path('cache'));
        self::assertSame($this->basePath . '/storage/cache', cache_path());
        self::assertSame($this->basePath . '/storage/logs/app.log', logs_path('app.log'));
        self::assertSame($this->basePath . '/resources/views/emails', view_path('emails'));
    }

    public function testEnvironmentAndRuntimeHelpersReflectTheCurrentApplication(): void
    {
        new Application($this->basePath);

        self::assertTrue(is_local());
        self::assertFalse(is_production());
        self::assertTrue(running_in_console());
    }

    public function testModuleDbAndDispatchHelpersUseSharedApplicationBindings(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $this->handlersBooted = true;

        self::assertTrue(has_module('blog'));
        self::assertSame('Blog Module', module('blog')->name());
        self::assertInstanceOf(ConnectionManager::class, db());

        $event = new \stdClass();

        self::assertSame($event, dispatch($event));
    }
}
