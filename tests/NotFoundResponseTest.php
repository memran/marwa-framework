<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use GuzzleHttp\Psr7\ServerRequest;
use League\Route\Http\Exception\NotFoundException;
use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Middlewares\RouterMiddleware;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Supports\Runtime;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A 404 body is worthless under a 200 status: a crawler indexes the page, a
 * monitor reports the site healthy, and an HTTP client's own error handling
 * never runs. Every branch of the not-found renderer is pinned here because
 * the two that were wrong were the two without an explicit status.
 */
final class NotFoundResponseTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-notfound-' . bin2hex(random_bytes(6));
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/resources/views/errors', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_DEBUG=false\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        Runtime::setConsoleOverride(false);
        $this->removeDirectory($this->basePath);
    }

    public function testAJsonRequestForAMissingRouteIsAnswered404(): void
    {
        $response = $this->dispatchMissingRoute('application/json');

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        /** @var array{status:int,error:string} $payload */
        $payload = json_decode((string) $response->getBody(), true);
        self::assertSame(404, $payload['status']);
        self::assertSame('Not Found', $payload['error']);
    }

    public function testTheBuiltInHtmlPageForAMissingRouteIsAnswered404(): void
    {
        $response = $this->dispatchMissingRoute('text/html');

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('404', (string) $response->getBody());
    }

    public function testAnApplicationErrorTemplateForAMissingRouteIsAnswered404(): void
    {
        file_put_contents($this->basePath . '/config/app.php', <<<'PHP'
<?php

return [
    'error404' => [
        'template' => 'errors/404',
    ],
];
PHP);
        file_put_contents($this->basePath . '/resources/views/errors/404.twig', 'Gone: {{ path }}');

        $response = $this->dispatchMissingRoute('text/html');

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('Gone: /no/such/path', (string) $response->getBody());
    }

    /**
     * The path is reflected into the page, so it is escaped on the way in.
     */
    public function testTheRequestedPathIsEscapedInTheJsonMessage(): void
    {
        $response = $this->dispatchMissingRoute('application/json', '/"><script>alert(1)</script>');

        self::assertSame(404, $response->getStatusCode());
        self::assertStringNotContainsString('<script>', (string) $response->getBody());
    }

    private function dispatchMissingRoute(string $accept, string $path = '/no/such/path'): ResponseInterface
    {
        // Built here, not in setUp, so a test may write config/app.php first.
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;

        // What AppBootstrapper does with the app config, without booting
        // providers, modules and the database to get at it.
        $config = $app->make(Config::class);
        self::assertInstanceOf(Config::class, $config);
        $config->loadIfExists('app.php');

        $app->container()->addShared(RouterAdapter::class, new class () {
            public function dispatch(ServerRequestInterface $request): ResponseInterface
            {
                throw new NotFoundException();
            }
        }, true);

        $request = (new ServerRequest('GET', 'http://localhost' . $path))
            ->withHeader('Accept', $accept);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        return (new RouterMiddleware())->process($request, $handler);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($directory);
    }
}
