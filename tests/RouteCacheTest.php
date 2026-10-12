<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Laminas\Diactoros\ServerRequest;
use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Tests\Fixtures\Controllers\CachedRouteController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class RouteCacheTest extends TestCase
{
    private string $basePath;
    private string $cacheFile;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-route-cache-' . bin2hex(random_bytes(6));
        $this->cacheFile = $this->basePath . '/storage/cache/routes.php';
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/routes', 0777, true);
        file_put_contents($this->basePath . '/config/module.php', "<?php\n\nreturn ['enabled' => false];\n");

        CachedRouteController::$instances = 0;
        unset($GLOBALS['marwa_route_file_loaded']);
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->cacheFile,
            $this->basePath . '/routes/web.php',
            $this->basePath . '/config/module.php',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/storage/cache');
        @rmdir($this->basePath . '/storage');
        @rmdir($this->basePath . '/routes');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $GLOBALS['marwa_route_file_loaded'],
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

    public function testRouteCacheCompilesRoutesFromRouteFiles(): void
    {
        $this->writeEnv('production');
        $this->writeControllerRoutes();

        $tester = $this->runRouteCache();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('(1 routes)', $tester->getDisplay());
        self::assertFileExists($this->cacheFile);
        self::assertStringContainsString('/cached', (string) file_get_contents($this->cacheFile));
    }

    public function testProductionDispatchesFromCacheWithoutLoadingRouteFiles(): void
    {
        $this->writeEnv('production');
        $this->writeControllerRoutes();
        self::assertSame(Command::SUCCESS, $this->runRouteCache()->getStatusCode());
        $this->resetApp();

        // The route file now only records that it ran: the cache must win.
        file_put_contents($this->basePath . '/routes/web.php', "<?php\n\n\$GLOBALS['marwa_route_file_loaded'] = true;\n");

        $router = $this->bootHttpRouter();

        self::assertArrayNotHasKey('marwa_route_file_loaded', $GLOBALS);
        self::assertSame(0, CachedRouteController::$instances);

        $response = $router->dispatch(new ServerRequest([], [], '/cached', 'GET'));

        self::assertSame('cached', (string) $response->getBody());
        self::assertSame(1, CachedRouteController::$instances);
    }

    public function testRouteCacheIsIgnoredOutsideProduction(): void
    {
        $this->writeEnv('local');
        mkdir(dirname($this->cacheFile), 0777, true);
        file_put_contents($this->cacheFile, "<?php\n\nthrow new \\LogicException('Stale route cache must not be loaded.');\n");
        file_put_contents($this->basePath . '/routes/web.php', "<?php\n\n\$GLOBALS['marwa_route_file_loaded'] = true;\n");

        $this->bootHttpRouter();

        self::assertTrue($GLOBALS['marwa_route_file_loaded'] ?? false);
    }

    public function testRouteCacheCommandSkipsOutsideProduction(): void
    {
        $this->writeEnv('local');
        $this->writeControllerRoutes();

        $tester = $this->runRouteCache();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('production-only', $tester->getDisplay());
        self::assertFileDoesNotExist($this->cacheFile);
    }

    public function testRouteCacheReportsClosureRoutes(): void
    {
        $this->writeEnv('production');
        file_put_contents(
            $this->basePath . '/routes/web.php',
            "<?php\n\nrouter()->get('/closure', static fn () => 'closure')->register();\n"
        );

        $tester = $this->runRouteCache();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Unable to cache routes', $tester->getDisplay());
        self::assertStringContainsString("[Controller::class, 'method']", $tester->getDisplay());
        self::assertFileDoesNotExist($this->cacheFile);
    }

    private function writeEnv(string $env): void
    {
        file_put_contents($this->basePath . '/.env', "APP_ENV={$env}\nTIMEZONE=UTC\n");
    }

    private function writeControllerRoutes(): void
    {
        file_put_contents(
            $this->basePath . '/routes/web.php',
            sprintf("<?php\n\nrouter()->get('/cached', [\\%s::class, 'show'])->register();\n", CachedRouteController::class)
        );
    }

    private function runRouteCache(): CommandTester
    {
        // Real CLI runs never load routes while booting, so neither may the test.
        Runtime::setConsoleOverride(true);

        try {
            $app = new Application($this->basePath);
            $this->handlersBooted = true;

            $tester = new CommandTester($app->console()->application()->find('route:cache'));
            $tester->execute([]);
        } finally {
            Runtime::setConsoleOverride(false);
        }

        return $tester;
    }

    private function bootHttpRouter(): RouterAdapter
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $this->handlersBooted = true;

        /** @var RouterAdapter $router */
        $router = $app->make(RouterAdapter::class);

        return $router;
    }

    private function resetApp(): void
    {
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        restore_error_handler();
        restore_exception_handler();
    }
}
