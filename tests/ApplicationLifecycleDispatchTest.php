<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Laminas\Diactoros\ServerRequest;
use Marwa\Framework\Adapters\Event\AppBooted;
use Marwa\Framework\Adapters\Event\ApplicationBootstrapping;
use Marwa\Framework\Adapters\Event\ApplicationStarted;
use Marwa\Framework\Adapters\Event\ConsoleBootstrapped;
use Marwa\Framework\Adapters\Event\ErrorHandlerBootstrapped;
use Marwa\Framework\Adapters\Event\ProvidersBootstrapped;
use Marwa\Framework\Adapters\Event\RequestHandled;
use Marwa\Framework\Adapters\Event\RequestHandlingStarted;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Tests\Fixtures\Listeners\LifecycleRecorder;
use PHPUnit\Framework\TestCase;

final class ApplicationLifecycleDispatchTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->handlersBooted = false;
        $this->basePath = sys_get_temp_dir() . '/marwa-lifecycle-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/routes', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents(
            $this->basePath . '/routes/web.php',
            <<<'PHP'
<?php

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/lifecycle', fn () => Response::json(['ok' => true]))->register();
PHP
        );
        file_put_contents(
            $this->basePath . '/config/event.php',
            <<<'PHP'
<?php

use Marwa\Framework\Adapters\Event\AppBooted;
use Marwa\Framework\Adapters\Event\ApplicationBootstrapping;
use Marwa\Framework\Adapters\Event\ApplicationStarted;
use Marwa\Framework\Adapters\Event\ConsoleBootstrapped;
use Marwa\Framework\Adapters\Event\ErrorHandlerBootstrapped;
use Marwa\Framework\Adapters\Event\ModulesBootstrapped;
use Marwa\Framework\Adapters\Event\ProvidersBootstrapped;
use Marwa\Framework\Adapters\Event\RequestHandled;
use Marwa\Framework\Adapters\Event\RequestHandlingStarted;
use Marwa\Framework\Tests\Fixtures\Listeners\LifecycleRecorder;

return [
    'listeners' => [
        ApplicationStarted::class => [[LifecycleRecorder::class, 'record']],
        ApplicationBootstrapping::class => [[LifecycleRecorder::class, 'record']],
        ProvidersBootstrapped::class => [[LifecycleRecorder::class, 'record']],
        ErrorHandlerBootstrapped::class => [[LifecycleRecorder::class, 'record']],
        ModulesBootstrapped::class => [[LifecycleRecorder::class, 'record']],
        AppBooted::class => [[LifecycleRecorder::class, 'record']],
        RequestHandlingStarted::class => [[LifecycleRecorder::class, 'record']],
        RequestHandled::class => [[LifecycleRecorder::class, 'record']],
        ConsoleBootstrapped::class => [[LifecycleRecorder::class, 'record']],
    ],
];
PHP
        );

        LifecycleRecorder::reset();
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/event.php',
            $this->basePath . '/routes/web.php',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/routes');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
        LifecycleRecorder::reset();
    }

    public function testApplicationDispatchesLifecycleEventsAcrossBootstrapHttpAndConsole(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $this->handlersBooted = true;

        $kernel = $app->make(HttpKernel::class);
        $response = $kernel->handle(new ServerRequest(uri: '/lifecycle', method: 'GET'));

        self::assertSame(200, $response->getStatusCode());

        $app->console()->application();

        self::assertSame([
            ApplicationStarted::class,
            ErrorHandlerBootstrapped::class,
            ApplicationBootstrapping::class,
            ProvidersBootstrapped::class,
            AppBooted::class,
            RequestHandlingStarted::class,
            RequestHandled::class,
            ConsoleBootstrapped::class,
        ], LifecycleRecorder::$events);
    }
}
