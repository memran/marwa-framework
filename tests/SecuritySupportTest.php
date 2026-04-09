<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use GuzzleHttp\Psr7\Response;
use Laminas\Diactoros\ServerRequest;
use Marwa\Framework\Application;
use Marwa\Framework\Config\SecurityConfig;
use Marwa\Framework\Contracts\SecurityInterface;
use Marwa\Framework\Middlewares\SecurityMiddleware;
use Marwa\Framework\Security\RiskAnalyzer;
use Marwa\Framework\Security\Security;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Tests\Fixtures\Security\ArrayCache;
use Marwa\Framework\Tests\Fixtures\Security\ArraySession;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class SecuritySupportTest extends TestCase
{
    private string $basePath;
    private string $riskLog;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-security-' . bin2hex(random_bytes(6));
        $this->riskLog = $this->basePath . '/storage/security/risk.jsonl';
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents(
            $this->basePath . '/config/security.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'csrf' => [
        'enabled' => true,
        'except' => ['webhook/*'],
    ],
    'trustedHosts' => ['example.com'],
    'trustedOrigins' => ['https://example.com'],
    'throttle' => [
        'enabled' => true,
        'prefix' => 'security-tests',
        'limit' => 2,
        'window' => 60,
    ],
    'risk' => [
        'enabled' => true,
        'logPath' => '{$this->riskLog}',
        'pruneAfterDays' => 30,
        'topCount' => 5,
    ],
];
PHP
        );
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/config/security.php');
        @unlink($this->basePath . '/.env');
        @unlink($this->riskLog);
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/storage/security');
        @rmdir($this->basePath . '/storage');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
    }

    public function testSecurityConfigExposesSafeDefaults(): void
    {
        $app = new Application($this->basePath);
        $defaults = SecurityConfig::defaults($app);

        self::assertTrue($defaults['enabled']);
        self::assertTrue($defaults['csrf']['enabled']);
        self::assertSame('_token', $defaults['csrf']['field']);
        self::assertSame('X-CSRF-TOKEN', $defaults['csrf']['header']);
        self::assertSame('__marwa_csrf_token', $defaults['csrf']['token']);
        self::assertSame(['POST', 'PUT', 'PATCH', 'DELETE'], $defaults['csrf']['methods']);
        self::assertSame([], $defaults['trustedHosts']);
        self::assertSame([], $defaults['trustedOrigins']);
        self::assertTrue($defaults['throttle']['enabled']);
        self::assertTrue($defaults['risk']['enabled']);
        self::assertSame($this->riskLog, $defaults['risk']['logPath']);
        self::assertSame(30, $defaults['risk']['pruneAfterDays']);
    }

    public function testSecurityServiceGeneratesTokensAndValidatesState(): void
    {
        $app = new Application($this->basePath);
        $security = new Security($app, new Config($this->basePath . '/config'), new ArrayCache(), new ArraySession());

        $token = $security->csrfToken();

        self::assertNotSame('', $token);
        self::assertSame($token, $security->csrfToken());
        self::assertTrue($security->validateCsrfToken($token));
        self::assertFalse($security->validateCsrfToken('invalid'));
        self::assertStringContainsString('name="_token"', $security->csrfField());
        self::assertTrue($security->isTrustedHost('example.com'));
        self::assertFalse($security->isTrustedHost('evil.test'));
        self::assertTrue($security->isTrustedOrigin('https://example.com'));
        self::assertFalse($security->isTrustedOrigin('https://evil.test'));
        self::assertTrue($security->isCsrfProtected('POST', '/account'));
        self::assertFalse($security->isCsrfProtected('GET', '/account'));
        self::assertFalse($security->isCsrfProtected('POST', '/webhook/ping'));
        self::assertSame('invoice-2026.pdf', $security->sanitizeFilename('../invoice 2026.pdf'));
        self::assertSame($this->basePath . '/storage/app/uploads/avatar.png', $security->safePath('uploads/avatar.png', $this->basePath . '/storage/app'));
    }

    public function testSecurityMiddlewareRecordsRiskSignals(): void
    {
        $app = new Application($this->basePath);
        $riskAnalyzer = new RiskAnalyzer($app, new Config($this->basePath . '/config'), new \Psr\Log\NullLogger());
        $request = new ServerRequest(
            serverParams: ['REMOTE_ADDR' => '127.0.0.1'],
            uri: 'https://evil.test/account',
            method: 'POST',
            parsedBody: ['_token' => 'token-123']
        );
        $handler = $this->createMock(RequestHandlerInterface::class);
        $security = $this->createMock(SecurityInterface::class);

        $security->method('isEnabled')->willReturn(true);
        $security->expects(self::once())->method('isTrustedHost')->with('evil.test')->willReturn(false);

        $handler->expects(self::never())->method('handle');

        $middleware = new SecurityMiddleware($security, $riskAnalyzer);

        self::assertSame(403, $middleware->process($request, $handler)->getStatusCode());
        self::assertFileExists($this->riskLog);
        self::assertStringContainsString('trusted-host', (string) file_get_contents($this->riskLog));
    }

    public function testSecurityMiddlewareBlocksAndAllowsRequests(): void
    {
        $security = $this->createMock(SecurityInterface::class);
        $request = new ServerRequest(
            serverParams: ['REMOTE_ADDR' => '127.0.0.1'],
            uri: 'https://example.com/account',
            method: 'POST',
            parsedBody: ['_token' => 'token-123']
        );
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = new Response(200, ['Content-Type' => 'text/html'], '<html><body>test</body></html>');

        $security->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $security->expects(self::once())
            ->method('isTrustedHost')
            ->with('example.com')
            ->willReturn(true);
        $security->expects(self::once())
            ->method('isCsrfProtected')
            ->with('POST', '/account')
            ->willReturn(true);
        $security->expects(self::once())
            ->method('configuration')
            ->willReturn([
                'csrf' => [
                    'header' => 'X-CSRF-TOKEN',
                    'field' => '_token',
                ],
            ]);
        $security->expects(self::once())
            ->method('validateCsrfToken')
            ->with('token-123')
            ->willReturn(true);
        $security->expects(self::once())
            ->method('throttle')
            ->with('127.0.0.1:POST:account')
            ->willReturn(true);

        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $middleware = new SecurityMiddleware($security);

        $result = $middleware->process($request, $handler);

        self::assertSame(200, $result->getStatusCode());
        self::assertEquals('nosniff', $result->getHeaderLine('X-Content-Type-Options'));
        self::assertEquals('DENY', $result->getHeaderLine('X-Frame-Options'));
    }

    public function testSecurityMiddlewareRejectsCsrfMismatch(): void
    {
        $security = $this->createMock(SecurityInterface::class);
        $request = new ServerRequest(
            serverParams: ['REMOTE_ADDR' => '127.0.0.1'],
            uri: 'https://example.com/account',
            method: 'POST',
            parsedBody: ['_token' => 'bad']
        );
        $handler = $this->createMock(RequestHandlerInterface::class);

        $security->method('isEnabled')->willReturn(true);
        $security->method('isTrustedHost')->willReturn(true);
        $security->method('isTrustedOrigin')->willReturn(true);
        $security->method('isCsrfProtected')->willReturn(true);
        $security->method('configuration')->willReturn([
            'csrf' => [
                'header' => 'X-CSRF-TOKEN',
                'field' => '_token',
            ],
        ]);
        $security->method('validateCsrfToken')->willReturn(false);

        $handler->expects(self::never())->method('handle');

        $middleware = new SecurityMiddleware($security);

        $response = $middleware->process($request, $handler);

        self::assertSame(419, $response->getStatusCode());
    }
}
