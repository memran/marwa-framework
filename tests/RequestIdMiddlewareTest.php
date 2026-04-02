<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Middlewares\RequestIdMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestIdMiddlewareTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-middleware-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "TIMEZONE=UTC\n");
        $GLOBALS['marwa_app'] = new Application($this->basePath);
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/.env');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['TIMEZONE'], $_SERVER['TIMEZONE']);
    }

    public function testItPreservesValidIncomingRequestIds(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $requestWithAttribute = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->expects(self::once())
            ->method('getHeaderLine')
            ->with('X-Request-ID')
            ->willReturn('req-123');
        $request->expects(self::once())
            ->method('withAttribute')
            ->with('request_id', 'req-123')
            ->willReturn($requestWithAttribute);

        $handler->expects(self::once())
            ->method('handle')
            ->with($requestWithAttribute)
            ->willReturn($response);

        $response->expects(self::once())
            ->method('withHeader')
            ->with('X-Request-ID', 'req-123')
            ->willReturnSelf();

        $middleware = new RequestIdMiddleware();

        self::assertSame($response, $middleware->process($request, $handler));
    }

    public function testItReplacesUnsafeIncomingRequestIds(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $requestWithAttribute = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->expects(self::once())
            ->method('getHeaderLine')
            ->with('X-Request-ID')
            ->willReturn("bad\r\nheader");
        $request->expects(self::once())
            ->method('withAttribute')
            ->with('request_id', self::matchesRegularExpression('/^[a-f0-9]{32}$/'))
            ->willReturn($requestWithAttribute);

        $handler->expects(self::once())
            ->method('handle')
            ->with($requestWithAttribute)
            ->willReturn($response);

        $response->expects(self::once())
            ->method('withHeader')
            ->with('X-Request-ID', self::matchesRegularExpression('/^[a-f0-9]{32}$/'))
            ->willReturnSelf();

        $middleware = new RequestIdMiddleware();

        self::assertSame($response, $middleware->process($request, $handler));
    }
}
