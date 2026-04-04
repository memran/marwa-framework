<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\HttpClientInterface;
use Marwa\Framework\Supports\Http;
use PHPUnit\Framework\TestCase;

final class HttpSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-http-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents(
            $this->basePath . '/config/http.php',
            <<<'PHP'
<?php

$history = [];
$GLOBALS['marwa_http_history'] = &$history;
$handler = \GuzzleHttp\HandlerStack::create(
    new \GuzzleHttp\Handler\MockHandler([
        new \GuzzleHttp\Psr7\Response(200, [], 'ok'),
    ])
);
$handler->push(\GuzzleHttp\Middleware::history($history));

return [
    'default' => 'api',
    'clients' => [
        'api' => [
            'base_uri' => 'https://example.test',
            'timeout' => 12,
            'connect_timeout' => 4,
            'handler' => $handler,
            'headers' => [
                'X-Base' => 'framework',
            ],
        ],
    ],
];
PHP
        );
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
    }

    public function testHttpClientLoadsConfiguredClientAndMergesRequestOptions(): void
    {
        $app = new Application($this->basePath);
        $http = $app->http();

        self::assertInstanceOf(Http::class, $http);
        self::assertInstanceOf(HttpClientInterface::class, $http);
        self::assertSame('api', $http->configuration()['default']);
        self::assertSame('https://example.test', (string) $http->client()->getConfig('base_uri'));
        self::assertSame('framework', $http->client()->getConfig('headers')['X-Base']);

        $response = $http
            ->withHeaders(['X-Trace' => 'abc'])
            ->get('/users', ['query' => ['page' => 1]]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', (string) $response->getBody());

        $history = $GLOBALS['marwa_http_history'] ?? [];
        self::assertCount(1, $history);
        self::assertSame('GET', $history[0]['request']->getMethod());
        self::assertSame('https://example.test/users?page=1', (string) $history[0]['request']->getUri());
        self::assertSame('framework', $history[0]['request']->getHeaderLine('X-Base'));
        self::assertSame('abc', $history[0]['request']->getHeaderLine('X-Trace'));
    }

    public function testHttpHelperReturnsSharedClientInstance(): void
    {
        $app = new Application($this->basePath);

        self::assertSame($app->http(), http());
        self::assertInstanceOf(\GuzzleHttp\Client::class, http()->client());
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $current = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($current)) {
                $this->removeDirectory($current);
                continue;
            }

            @unlink($current);
        }

        @rmdir($path);
    }
}
