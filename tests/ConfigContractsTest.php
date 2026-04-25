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
use Marwa\Framework\Config\HttpConfig;
use Marwa\Framework\Config\LoggerConfig;
use Marwa\Framework\Config\MailConfig;
use Marwa\Framework\Config\ModuleConfig;
use Marwa\Framework\Config\NotificationConfig;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Config\ScheduleConfig;
use Marwa\Framework\Config\SecurityConfig;
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
        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-config-contracts-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'config', 0777, true);
        file_put_contents($this->basePath . DIRECTORY_SEPARATOR . '.env', "APP_DEBUG=false\nLOG_ENABLE=true\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . DIRECTORY_SEPARATOR . '.env');
        @rmdir($this->basePath . DIRECTORY_SEPARATOR . 'config');
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

    public function testSecurityMiddlewareIsPresentInTheDefaultStack(): void
    {
        $defaults = AppConfig::defaults();

        self::assertContains(\Marwa\Framework\Middlewares\SecurityMiddleware::class, $defaults['middlewares']);
    }

    public function testViewAndLoggerContractsBuildPathsFromApplicationBasePath(): void
    {
        $app = new Application($this->basePath);

        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/resources/views'),
            str_replace('/', DIRECTORY_SEPARATOR, ViewConfig::defaults($app)['viewsPath'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/cache/views'),
            str_replace('/', DIRECTORY_SEPARATOR, ViewConfig::defaults($app)['cachePath'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/resources/views/themes'),
            str_replace('/', DIRECTORY_SEPARATOR, ViewConfig::defaults($app)['themePath'])
        );
        self::assertSame('default', ViewConfig::defaults($app)['activeTheme']);
        self::assertSame('default', ViewConfig::defaults($app)['fallbackTheme']);
        self::assertSame('file', CacheConfig::defaults($app)['driver']);
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/cache/framework'),
            str_replace('/', DIRECTORY_SEPARATOR, CacheConfig::defaults($app)['file']['path'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/cache/framework.sqlite'),
            str_replace('/', DIRECTORY_SEPARATOR, CacheConfig::defaults($app)['sqlite']['path'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/app'),
            str_replace('/', DIRECTORY_SEPARATOR, StorageConfig::defaults($app)['disks']['local']['root'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/app/public'),
            str_replace('/', DIRECTORY_SEPARATOR, StorageConfig::defaults($app)['disks']['public']['root'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/logs'),
            str_replace('/', DIRECTORY_SEPARATOR, LoggerConfig::defaults($app)['storage']['path'])
        );
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

        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/cache/config.php'),
            str_replace('/', DIRECTORY_SEPARATOR, BootstrapConfig::defaults($app)['configCache'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/cache/routes.php'),
            str_replace('/', DIRECTORY_SEPARATOR, BootstrapConfig::defaults($app)['routeCache'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/cache/modules.php'),
            str_replace('/', DIRECTORY_SEPARATOR, ModuleConfig::defaults($app)['cache'])
        );
        self::assertSame(
            [str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/modules')],
            array_map(fn ($p) => str_replace('/', DIRECTORY_SEPARATOR, $p), ModuleConfig::defaults($app)['paths'])
        );
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
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/database/migrations'),
            str_replace('/', DIRECTORY_SEPARATOR, $defaults['migrationsPath'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/database/seeders'),
            str_replace('/', DIRECTORY_SEPARATOR, $defaults['seedersPath'])
        );
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
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $app->basePath('storage/session')),
            str_replace('/', DIRECTORY_SEPARATOR, $defaults['savePath'])
        );
    }

    public function testSecurityConfigExposesExpectedDefaults(): void
    {
        $app = new Application($this->basePath);
        $defaults = SecurityConfig::defaults($app);

        self::assertTrue($defaults['enabled']);
        self::assertTrue($defaults['csrf']['enabled']);
        self::assertSame('X-CSRF-TOKEN', $defaults['csrf']['header']);
        self::assertSame('__marwa_csrf_token', $defaults['csrf']['token']);
        self::assertSame(['POST', 'PUT', 'PATCH', 'DELETE'], $defaults['csrf']['methods']);
        self::assertSame([], $defaults['trustedHosts']);
        self::assertSame([], $defaults['trustedOrigins']);
        self::assertTrue($defaults['throttle']['enabled']);
        self::assertTrue($defaults['risk']['enabled']);
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/security/risk.jsonl'),
            str_replace('/', DIRECTORY_SEPARATOR, $defaults['risk']['logPath'])
        );
        self::assertSame(30, $defaults['risk']['pruneAfterDays']);
    }

    public function testQueueAndScheduleConfigsExposeExpectedDefaults(): void
    {
        $app = new Application($this->basePath);

        self::assertTrue(QueueConfig::defaults($app)['enabled']);
        self::assertSame('default', QueueConfig::defaults($app)['default']);
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/queue'),
            str_replace('/', DIRECTORY_SEPARATOR, QueueConfig::defaults($app)['path'])
        );
        self::assertSame(90, QueueConfig::defaults($app)['retryAfter']);

        self::assertTrue(ScheduleConfig::defaults($app)['enabled']);
        self::assertSame('file', ScheduleConfig::defaults($app)['driver']);
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/framework/schedule'),
            str_replace('/', DIRECTORY_SEPARATOR, ScheduleConfig::defaults($app)['lockPath'])
        );
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/framework/schedule'),
            str_replace('/', DIRECTORY_SEPARATOR, ScheduleConfig::defaults($app)['file']['path'])
        );
        self::assertSame('schedule', ScheduleConfig::defaults($app)['cache']['namespace']);
        self::assertSame('sqlite', ScheduleConfig::defaults($app)['database']['connection']);
        self::assertSame('schedule_jobs', ScheduleConfig::defaults($app)['database']['table']);
        self::assertSame(0, ScheduleConfig::defaults($app)['defaultLoopSeconds']);
        self::assertSame(1, ScheduleConfig::defaults($app)['defaultSleepSeconds']);
    }

    public function testMailConfigExposesExpectedDefaults(): void
    {
        $app = new Application($this->basePath);
        $defaults = MailConfig::defaults($app);

        self::assertTrue($defaults['enabled']);
        self::assertSame('smtp', $defaults['driver']);
        self::assertSame('UTF-8', $defaults['charset']);
        self::assertSame('no-reply@example.com', $defaults['from']['address']);
        self::assertSame('MarwaPHP', $defaults['from']['name']);
        self::assertSame('127.0.0.1', $defaults['smtp']['host']);
        self::assertSame(1025, $defaults['smtp']['port']);
        self::assertSame('/usr/sbin/sendmail -bs', $defaults['sendmail']['path']);
    }

    public function testNotificationConfigExposesExpectedDefaults(): void
    {
        $app = new Application($this->basePath);
        $defaults = NotificationConfig::defaults($app);

        self::assertTrue($defaults['enabled']);
        self::assertSame(['mail'], $defaults['default']);
        self::assertArrayHasKey('mail', $defaults['channels']);
        self::assertArrayHasKey('database', $defaults['channels']);
        self::assertArrayHasKey('http', $defaults['channels']);
        self::assertArrayHasKey('sms', $defaults['channels']);
        self::assertArrayHasKey('kafka', $defaults['channels']);
        self::assertArrayHasKey('broadcast', $defaults['channels']);
        self::assertNull($defaults['channels']['kafka']['consumer']);
        self::assertSame('notifications', $defaults['channels']['kafka']['topic']);
        self::assertSame('marwa-framework', $defaults['channels']['kafka']['groupId']);
    }

    public function testHttpConfigExposesExpectedDefaults(): void
    {
        $app = new Application($this->basePath);
        $defaults = HttpConfig::defaults($app);

        self::assertTrue($defaults['enabled']);
        self::assertSame('default', $defaults['default']);
        self::assertArrayHasKey('default', $defaults['clients']);
        self::assertSame(30.0, $defaults['clients']['default']['timeout']);
        self::assertSame(10.0, $defaults['clients']['default']['connect_timeout']);
        self::assertFalse($defaults['clients']['default']['http_errors']);
        self::assertTrue($defaults['clients']['default']['verify']);
    }
}
