<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Config\AppConfig;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Config\CacheConfig;
use Marwa\Framework\Config\DatabaseConfig;
use Marwa\Framework\Config\ErrorConfig;
use Marwa\Framework\Config\EventConfig;
use Marwa\Framework\Config\LoggerConfig;
use Marwa\Framework\Config\ModuleConfig;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Config\ScheduleConfig;
use Marwa\Framework\Config\SessionConfig;
use Marwa\Framework\Config\StorageConfig;
use Marwa\Framework\Config\ViewConfig;
use Marwa\Framework\Middlewares\SessionMiddleware;
use PHPUnit\Framework\TestCase;

final class ConfigContractsTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-config-contracts-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_DEBUG=false\nLOG_ENABLE=true\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/.env');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_DEBUG'], $_ENV['LOG_ENABLE'], $_ENV['TIMEZONE'], $_SERVER['APP_DEBUG'], $_SERVER['LOG_ENABLE'], $_SERVER['TIMEZONE']);
    }

    public function testAppConfigExposesExpectedDefaultKeys(): void
    {
        $defaults = AppConfig::defaults();

        self::assertArrayHasKey('providers', $defaults);
        self::assertArrayHasKey('middlewares', $defaults);
        self::assertArrayHasKey('debugbar', $defaults);
        self::assertArrayHasKey('collectors', $defaults);
        self::assertContains(SessionMiddleware::class, $defaults['middlewares']);
    }

    public function testViewAndLoggerContractsBuildPathsFromApplicationBasePath(): void
    {
        $app = new Application($this->basePath);

        self::assertSame($this->basePath . '/resources/views', ViewConfig::defaults($app)['viewsPath']);
        self::assertSame($this->basePath . '/storage/cache/views', ViewConfig::defaults($app)['cachePath']);
        self::assertSame($this->basePath . '/storage/cache/framework.sqlite', CacheConfig::defaults($app)['sqlite']['path']);
        self::assertSame($this->basePath . '/storage/app', StorageConfig::defaults($app)['disks']['local']['root']);
        self::assertSame($this->basePath . '/storage/app/public', StorageConfig::defaults($app)['disks']['public']['root']);
        self::assertSame($this->basePath . '/storage/logs', LoggerConfig::defaults($app)['storage']['path']);
    }

    public function testEventConfigDefaultsAreEmptyLists(): void
    {
        $defaults = EventConfig::defaults();

        self::assertSame([], $defaults['listeners']);
        self::assertSame([], $defaults['subscribers']);
    }

    public function testErrorConfigDefaultsAreProductionSafe(): void
    {
        $app = new Application($this->basePath);
        $defaults = ErrorConfig::defaults($app);

        self::assertTrue($defaults['enabled']);
        self::assertTrue($defaults['useLogger']);
        self::assertTrue($defaults['useDebugReporter']);
        self::assertSame('production', $defaults['environment']);
    }

    public function testBootstrapAndModuleContractsExposeExpectedPaths(): void
    {
        $app = new Application($this->basePath);

        self::assertSame($this->basePath . '/bootstrap/cache/config.php', BootstrapConfig::defaults($app)['configCache']);
        self::assertSame($this->basePath . '/bootstrap/cache/routes.php', BootstrapConfig::defaults($app)['routeCache']);
        self::assertSame($this->basePath . '/bootstrap/cache/modules.php', ModuleConfig::defaults($app)['cache']);
        self::assertSame([$this->basePath . '/modules'], ModuleConfig::defaults($app)['paths']);
        self::assertFalse(ModuleConfig::defaults($app)['forceRefresh']);
        self::assertSame(['commands'], ModuleConfig::defaults($app)['commandPaths']);
        self::assertSame(['Console/Commands', 'src/Console/Commands'], ModuleConfig::defaults($app)['commandConventions']);
    }

    public function testDatabaseConfigExposesExpectedDefaults(): void
    {
        $app = new Application($this->basePath);
        $defaults = DatabaseConfig::defaults($app);

        self::assertFalse($defaults['enabled']);
        self::assertSame('sqlite', $defaults['default']);
        self::assertSame($this->basePath . '/database/migrations', $defaults['migrationsPath']);
        self::assertSame($this->basePath . '/database/seeders', $defaults['seedersPath']);
        self::assertSame('Database\\Seeders', $defaults['seedersNamespace']);
    }

    public function testSessionConfigExposesSecureDefaults(): void
    {
        $app = new Application($this->basePath);
        $defaults = SessionConfig::defaults($app);

        self::assertTrue($defaults['enabled']);
        self::assertFalse($defaults['autoStart']);
        self::assertSame('marwa_session', $defaults['name']);
        self::assertTrue($defaults['httpOnly']);
        self::assertSame('Lax', $defaults['sameSite']);
        self::assertTrue($defaults['encrypt']);
    }

    public function testQueueAndScheduleConfigsExposeExpectedDefaults(): void
    {
        $app = new Application($this->basePath);

        self::assertTrue(QueueConfig::defaults($app)['enabled']);
        self::assertSame('default', QueueConfig::defaults($app)['default']);
        self::assertSame($this->basePath . '/storage/queue', QueueConfig::defaults($app)['path']);
        self::assertSame(90, QueueConfig::defaults($app)['retryAfter']);

        self::assertTrue(ScheduleConfig::defaults($app)['enabled']);
        self::assertSame($this->basePath . '/storage/framework/schedule', ScheduleConfig::defaults($app)['lockPath']);
        self::assertSame(1, ScheduleConfig::defaults($app)['defaultLoopSeconds']);
        self::assertSame(1, ScheduleConfig::defaults($app)['defaultSleepSeconds']);
    }
}
