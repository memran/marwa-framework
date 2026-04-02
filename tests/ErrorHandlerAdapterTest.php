<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DebugBar\DebugBar;
use Marwa\ErrorHandler\Contracts\DebugReporterInterface;
use Marwa\ErrorHandler\ErrorHandler;
use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Tests\Fixtures\Error\SpyRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ErrorHandlerAdapterTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->handlersBooted = false;
        $this->basePath = sys_get_temp_dir() . '/marwa-error-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_NAME=\"Marwa Test App\"\nTIMEZONE=UTC\n");
        SpyRenderer::reset();
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/app.php',
            $this->basePath . '/config/error.php',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['APP_NAME'],
            $_ENV['DEBUGBAR_ENABLED'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_NAME'],
            $_SERVER['DEBUGBAR_ENABLED'],
            $_SERVER['TIMEZONE']
        );

        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
        SpyRenderer::reset();
    }

    public function testAppBootstrapperBootsErrorHandlerWithLoggerAndConfiguredRenderer(): void
    {
        file_put_contents(
            $this->basePath . '/config/error.php',
            <<<'PHP'
<?php

use Marwa\Framework\Tests\Fixtures\Error\SpyRenderer;

return [
    'renderer' => SpyRenderer::class,
];
PHP
        );

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        $adapter = $app->make(ErrorHandlerAdapter::class);
        $handler = $adapter->handler();
        $this->handlersBooted = $handler instanceof ErrorHandler;

        self::assertInstanceOf(ErrorHandler::class, $handler);
        self::assertSame($app->make(LoggerInterface::class), $this->readProperty($handler, 'logger'));
        self::assertInstanceOf(SpyRenderer::class, $this->readProperty($handler, 'renderer'));
        self::assertTrue((bool) $this->readProperty($handler, 'registered'));
    }

    public function testDebugReporterIsWiredWhenDebugbarServiceIsAvailable(): void
    {
        file_put_contents(
            $this->basePath . '/config/app.php',
            <<<'PHP'
<?php

return [
    'debugbar' => true,
];
PHP
        );
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_NAME=\"Marwa Test App\"\nDEBUGBAR_ENABLED=true\nTIMEZONE=UTC\n");

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        $handler = $app->make(ErrorHandlerAdapter::class)->handler();
        $this->handlersBooted = $handler instanceof ErrorHandler;
        self::assertInstanceOf(ErrorHandler::class, $handler);

        $debugReporter = $this->readProperty($handler, 'debugReporter');
        self::assertInstanceOf(DebugReporterInterface::class, $debugReporter);

        /** @var DebugBar $debugbar */
        $debugbar = $app->make('debugbar');
        $debugReporter->report(new \RuntimeException('Test exception'));

        self::assertCount(1, $debugbar->state()->exceptions);
    }

    public function testConsoleBootAlsoBootsTheSharedErrorHandler(): void
    {
        file_put_contents(
            $this->basePath . '/config/error.php',
            <<<'PHP'
<?php

use Marwa\Framework\Tests\Fixtures\Error\SpyRenderer;

return [
    'renderer' => SpyRenderer::class,
];
PHP
        );

        $app = new Application($this->basePath);
        $app->console()->application();

        $handler = $app->make(ErrorHandlerAdapter::class)->handler();
        $this->handlersBooted = $handler instanceof ErrorHandler;

        self::assertInstanceOf(ErrorHandler::class, $handler);
        self::assertInstanceOf(SpyRenderer::class, $this->readProperty($handler, 'renderer'));
    }

    public function testErrorHandlingCanBeDisabledFromConfig(): void
    {
        file_put_contents(
            $this->basePath . '/config/error.php',
            <<<'PHP'
<?php

return [
    'enabled' => false,
];
PHP
        );

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        self::assertNull($app->make(ErrorHandlerAdapter::class)->handler());
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);

        return $reflection->getValue($object);
    }
}
