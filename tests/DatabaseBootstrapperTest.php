<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\ORM\Model;
use Marwa\DB\Schema\Schema;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use PHPUnit\Framework\TestCase;

final class DatabaseBootstrapperTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->handlersBooted = false;
        $this->basePath = sys_get_temp_dir() . '/marwa-db-bootstrapper-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
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
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/app.php',
            $this->basePath . '/config/database.php',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/database');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $GLOBALS['cm'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testDatabaseBootstrapperBindsConnectionManagerAndDbService(): void
    {
        file_put_contents(
            $this->basePath . '/config/app.php',
            <<<'PHP'
<?php

return [
    'useDebugPanel' => true,
];
PHP
        );

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $this->handlersBooted = true;

        self::assertTrue($app->has(ConnectionManager::class));
        self::assertTrue($app->has('db'));
        self::assertInstanceOf(ConnectionManager::class, $app->make(ConnectionManager::class));
        self::assertInstanceOf(ConnectionManager::class, $app->make('db'));
        self::assertNotNull($app->make(ConnectionManager::class)->getDebugPanel());

        $modelConnectionManager = $this->readStaticProperty(Model::class, 'cm');
        $schemaFactory = $this->readStaticProperty(Schema::class, 'factory');

        self::assertInstanceOf(ConnectionManager::class, $modelConnectionManager);
        self::assertNotNull($schemaFactory);
    }

    public function testDatabaseBootstrapperKeepsDebugPanelDisabledByDefault(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $this->handlersBooted = true;

        self::assertInstanceOf(ConnectionManager::class, $app->make(ConnectionManager::class));
        self::assertNull($app->make(ConnectionManager::class)->getDebugPanel());
    }

    public function testDatabaseBootstrapperKeepsDebugPanelDisabledWhenAppConfigFlagIsFalse(): void
    {
        file_put_contents(
            $this->basePath . '/config/app.php',
            <<<'PHP'
<?php

return [
    'useDebugPanel' => false,
];
PHP
        );

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $this->handlersBooted = true;

        self::assertInstanceOf(ConnectionManager::class, $app->make(ConnectionManager::class));
        self::assertNull($app->make(ConnectionManager::class)->getDebugPanel());
    }

    private function readStaticProperty(string $class, string $property): mixed
    {
        $reflection = new \ReflectionProperty($class, $property);

        return $reflection->getValue();
    }
}
