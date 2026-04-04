<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use Marwa\Framework\Contracts\SecurityInterface;
use Marwa\Framework\Security\RiskAnalyzer;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SecurityMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SecurityInterface $security,
        private ?RiskAnalyzer $riskAnalyzer = null
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->security->isEnabled()) {
            return $handler->handle($request);
        }

        $config = $this->security->configuration();

        $host = $request->getUri()->getHost();

        if ($host !== '' && !$this->security->isTrustedHost($host)) {
            $this->recordRisk($request, 'trusted-host', 'Rejected request with an untrusted host.');
            return Response::forbidden('Forbidden');
        }

        $origin = $this->extractOrigin($request);

        if ($origin !== '' && !$this->security->isTrustedOrigin($origin)) {
            $this->recordRisk($request, 'trusted-origin', 'Rejected request with an untrusted origin.', [
                'origin' => $origin,
            ]);
            return Response::forbidden('Forbidden');
        }

        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        if ($this->security->isCsrfProtected($method, $path)) {
            $token = $this->extractCsrfToken($request, $config);

            if (!$this->security->validateCsrfToken($token)) {
                $this->recordRisk($request, 'csrf', 'Rejected request with an invalid CSRF token.');
                return Response::error('CSRF token mismatch', 419);
            }
        }

        if (!$this->security->throttle($this->throttleKey($request))) {
            $this->recordRisk($request, 'throttle', 'Rejected request after exceeding the throttling limit.');
            return Response::json([
                'success' => false,
                'message' => 'Too Many Requests',
                'errors' => [],
                'timestamp' => time(),
            ], 429, [
                'Retry-After' => (string) $this->security->throttleWindow(),
            ]);
        }

        return $handler->handle($request);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function recordRisk(ServerRequestInterface $request, string $category, string $message, array $context = []): void
    {
        if (!$this->riskAnalyzer instanceof RiskAnalyzer) {
            return;
        }

        $this->riskAnalyzer->recordRequest($request, $category, $message, $context, 80);
    }

    /**
     * @param array{csrf: array{header: string, field: string}} $config
     */
    private function extractCsrfToken(ServerRequestInterface $request, array $config): string
    {
        $headerName = $config['csrf']['header'];
        $headerToken = trim($request->getHeaderLine($headerName));

        if ($headerToken !== '') {
            return $headerToken;
        }

        $parsed = $request->getParsedBody();
        $field = $config['csrf']['field'];

        if (is_array($parsed) && isset($parsed[$field]) && is_string($parsed[$field])) {
            return trim($parsed[$field]);
        }

        if (is_object($parsed) && isset($parsed->{$field}) && is_string($parsed->{$field})) {
            return trim($parsed->{$field});
        }

        return '';
    }

    private function extractOrigin(ServerRequestInterface $request): string
    {
        $origin = trim($request->getHeaderLine('Origin'));

        if ($origin !== '') {
            return $origin;
        }

        $referer = trim($request->getHeaderLine('Referer'));

        if ($referer === '') {
            return '';
        }

        $parts = parse_url($referer);

        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $origin .= ':' . (string) $parts['port'];
        }

        return $origin;
    }

    private function throttleKey(ServerRequestInterface $request): string
    {
        $ip = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');
        $method = strtoupper($request->getMethod());
        $path = ltrim($request->getUri()->getPath(), '/');

        return implode(':', [$ip, $method, $path]);
    }
}
