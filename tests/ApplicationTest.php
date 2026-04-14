<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Navigation\MenuRegistry;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-app-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/.env');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
    }

    public function testEnvironmentReturnsCurrentEnvironmentWhenNoArgumentIsProvided(): void
    {
        $app = new Application($this->basePath);

        self::assertSame('testing', $app->environment());
        self::assertTrue($app->environment('testing'));
        self::assertFalse($app->environment('production'));
    }

    public function testMenuRegistryIsBoundAsASharedService(): void
    {
        $app = new Application($this->basePath);

        $first = $app->make(MenuRegistry::class);
        $second = $app->make(MenuRegistry::class);

        self::assertSame($first, $second);
    }
}
