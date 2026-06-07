<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use Marwa\ErrorHandler\Contracts\RendererInterface;
use Marwa\Framework\Facades\View;
use Throwable;

final class ErrorViewRenderer implements RendererInterface
{
    public function __construct(
        private RendererInterface $fallback,
        private string $template,
    ) {}

    public function renderException(Throwable $e, string $appName, bool $dev): void
    {
        $this->sendHtmlHeaders();

        try {
            $html = View::make($this->template, [
                'exception' => $e,
                'appName' => $appName,
                'dev' => $dev,
                'requestId' => $this->requestId(),
            ])->withStatus(500)->body();

            echo $html;
        } catch (Throwable) {
            $this->fallback->renderException($e, $appName, $dev);
        }
    }

    public function renderGeneric(string $appName): void
    {
        $this->sendHtmlHeaders();

        try {
            $html = View::make($this->template, [
                'exception' => null,
                'appName' => $appName,
                'dev' => false,
                'requestId' => $this->requestId(),
            ])->withStatus(500)->body();

            echo $html;
        } catch (Throwable) {
            $this->fallback->renderGeneric($appName);
        }
    }

    public function renderCli(Throwable $e, string $appName, bool $dev): void
    {
        $this->fallback->renderCli($e, $appName, $dev);
    }

    private function sendHtmlHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }

    private function requestId(): string
    {
        foreach (['HTTP_X_REQUEST_ID', 'HTTP_X_CORRELATION_ID'] as $headerName) {
            $candidate = $_SERVER[$headerName] ?? null;

            if (is_string($candidate) && preg_match('/\A[a-zA-Z0-9._:-]{1,128}\z/', $candidate) === 1) {
                return $candidate;
            }
        }

        try {
            return 'r-' . bin2hex(random_bytes(6));
        } catch (Throwable) {
            return 'r-' . str_replace('.', '', uniqid('', true));
        }
    }
}
