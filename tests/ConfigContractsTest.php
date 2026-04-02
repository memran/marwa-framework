<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Config\AppConfig;
use Marwa\Framework\Config\EventConfig;
use Marwa\Framework\Config\LoggerConfig;
use Marwa\Framework\Config\ViewConfig;
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
    }

    public function testViewAndLoggerContractsBuildPathsFromApplicationBasePath(): void
    {
        $app = new Application($this->basePath);

        self::assertSame($this->basePath . '/resources/views', ViewConfig::defaults($app)['viewsPath']);
        self::assertSame($this->basePath . '/storage/cache/views', ViewConfig::defaults($app)['cachePath']);
        self::assertSame($this->basePath . '/storage/logs', LoggerConfig::defaults($app)['storage']['path']);
    }

    public function testEventConfigDefaultsAreEmptyLists(): void
    {
        $defaults = EventConfig::defaults();

        self::assertSame([], $defaults['listeners']);
        self::assertSame([], $defaults['subscribers']);
    }
}
