<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Bootstrappers\ProviderBootstrapper;
use Marwa\Framework\Tests\Fixtures\Providers\CountingServiceProvider;
use PHPUnit\Framework\TestCase;

final class AppBootstrapperTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-bootstrapper-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");

        file_put_contents(
            $this->basePath . '/config/app.php',
            <<<'PHP'
<?php

use Marwa\Framework\Tests\Fixtures\Providers\CountingServiceProvider;

return [
    'providers' => [
        CountingServiceProvider::class,
        CountingServiceProvider::class,
    ],
];
PHP
        );

        CountingServiceProvider::$registerCalls = 0;
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/bootstrap/cache/config.php');
        @rmdir($this->basePath . '/bootstrap/cache');
        @rmdir($this->basePath . '/bootstrap');
        @unlink($this->basePath . '/config/app.php');
        @unlink($this->basePath . '/.env');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
        CountingServiceProvider::$registerCalls = 0;
    }

    public function testBootstrapLoadsAppConfigAndRegistersProvidersOnlyOnce(): void
    {
        $app = new Application($this->basePath);
        $bootstrapper = $app->make(AppBootstrapper::class);

        $first = $bootstrapper->bootstrap();
        $second = $bootstrapper->bootstrap();

        self::assertSame($first, $second);
        self::assertCount(2, $first['providers']);
        self::assertTrue($app->make(ProviderBootstrapper::class)->hasBootstrapped(CountingServiceProvider::class));
        self::assertSame(0, CountingServiceProvider::$registerCalls);
    }

    public function testBootstrapUsesConfigCacheWhenAvailable(): void
    {
        mkdir($this->basePath . '/bootstrap/cache', 0777, true);
        file_put_contents(
            $this->basePath . '/bootstrap/cache/config.php',
            <<<'PHP'
<?php

declare(strict_types=1);

return [
    'app' => [
        'providers' => [],
        'middlewares' => [],
        'debugbar' => true,
        'collectors' => ['cached'],
    ],
];
PHP
        );

        $app = new Application($this->basePath);
        $bootstrapper = $app->make(AppBootstrapper::class);
        $appConfig = $bootstrapper->bootstrap();

        self::assertTrue($appConfig['debugbar']);
        self::assertSame(['cached'], $appConfig['collectors']);
    }
}
