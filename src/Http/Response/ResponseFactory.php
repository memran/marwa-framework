<?php

namespace Marwa\App\Http\Response;

use Marwa\App\Contracts\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Stream;
use Marwa\App\Exceptions\InvalidArgumentException;

/**
 * Class ResponseFactory
 *
 * Concrete Laravel-style response factory using Laminas Diactoros.
 * Keeps responsibilities small and testable, avoids side-effects.
 */
final class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create a plain/text response.
     *
     * @param string|\Stringable $content
     * @param int $status
     * @param array<string,string|string[]> $headers
     */
    public function make(string|\Stringable $content = '', int $status = 200, array $headers = []): ResponseInterface
    {
        $body = (string)$content;
        $default = ['Content-Type' => 'text/plain; charset=UTF-8'];
        return $this->withHeaders(new TextResponse($body, $status, $default), $headers);
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array<string,string|string[]> $headers
     * @param int $options
     */
    public function json(mixed $data, int $status = 200, array $headers = [], int $options = 0): ResponseInterface
    {
        $default = ['Content-Type' => 'application/json; charset=UTF-8'];
        $resp = new JsonResponse($data, $status, $default, $options);
        return $this->withHeaders($resp, $headers);
    }

    /**
     * Create a redirect response.
     *
     * @param string $to
     * @param int $status
     * @param array<string,string|string[]> $headers
     */
    public function redirect(string $to, int $status = 302, array $headers = []): ResponseInterface
    {
        $resp = new RedirectResponse($to, $status, []);
        return $this->withHeaders($resp, $headers);
    }

    /**
     * Create an empty/no-content response.
     *
     * @param int $status
     * @param array<string,string|string[]> $headers
     */
    public function noContent(int $status = 204, array $headers = []): ResponseInterface
    {
        $resp = new Response('php://memory', $status, []);
        return $this->withHeaders($resp, $headers);
    }

    /**
     * Stream a response via a callback that echoes to output buffer.
     * Note: For high-throughput, prefer a PSR-7 Stream that reads from a resource.
     *
     * @param callable():void $callback
     * @param int $status
     * @param array<string,string|string[]> $headers
     */
    public function stream(callable $callback, int $status = 200, array $headers = []): ResponseInterface
    {
        $stream = new Stream('php://temp', 'wb+');
        // Capture stream output to avoid side-effects and keep PSR-7 purity.
        ob_start();
        try {
            $callback();
            $out = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $stream->write($out);
        $stream->rewind();

        $resp = (new Response())
            ->withStatus($status)
            ->withBody($stream);
        $resp = $resp->withHeader('Content-Type', 'application/octet-stream');

        return $this->withHeaders($resp, $headers);
    }

    /**
     * Serve a local file inline.
     *
     * @param string $path
     * @param string|null $name
     * @param array<string,string|string[]> $headers
     */
    public function file(string $path, ?string $name = null, array $headers = []): ResponseInterface
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException("File not readable: {$path}");
        }

        $stream = new Stream($path, 'rb');
        $resp = (new Response())
            ->withStatus(200)
            ->withBody($stream);

        $mime = $this->detectMime($path) ?? 'application/octet-stream';
        $resp = $resp->withHeader('Content-Type', $mime)
            ->withHeader('Content-Length', (string) filesize($path))
            ->withHeader('Accept-Ranges', 'bytes');

        if ($name) {
            $resp = $resp->withHeader(
                'Content-Disposition',
                'inline; filename="' . addslashes($name) . '"'
            );
        }

        return $this->withHeaders($resp, $headers);
    }

    /**
     * Force file download as attachment.
     *
     * @param string $path
     * @param string|null $name
     * @param array<string,string|string[]> $headers
     */
    public function download(string $path, ?string $name = null, array $headers = []): ResponseInterface
    {
        $name = $name ?: basename($path);
        $resp = $this->file($path, $name, $headers);
        return $resp->withHeader(
            'Content-Disposition',
            'attachment; filename="' . addslashes($name) . '"'
        );
    }

    /**
     * Add headers on a response (overriding duplicates).
     *
     * @param ResponseInterface $response
     * @param array<string,string|string[]> $headers
     */
    public function withHeaders(ResponseInterface $response, array $headers): ResponseInterface
    {
        foreach ($headers as $key => $value) {
            if (is_array($value)) {
                $response = $response->withHeader((string)$key, $value);
            } else {
                $response = $response->withHeader((string)$key, (string)$value);
            }
        }
        return $response;
    }

    /**
     * Attach a cookie (Set-Cookie header).
     *
     * @param ResponseInterface $response
     * @param Cookie $cookie
     */
    public function cookie(ResponseInterface $response, Cookie $cookie): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', $cookie->toHeader());
    }

    /**
     * Attach multiple cookies.
     *
     * @param ResponseInterface $response
     * @param array<int, Cookie> $cookies
     */
    public function withCookies(ResponseInterface $response, array $cookies): ResponseInterface
    {
        foreach ($cookies as $cookie) {
            if (!$cookie instanceof Cookie) {
                throw new InvalidArgumentException('withCookies expects an array of Cookie objects.');
            }
            $response = $this->cookie($response, $cookie);
        }
        return $response;
    }

    /**
     * Replace the body stream.
     *
     * @param ResponseInterface $response
     * @param StreamInterface $stream
     */
    public function withBody(ResponseInterface $response, StreamInterface $stream): ResponseInterface
    {
        return $response->withBody($stream);
    }

    /**
     * Set HTTP status.
     *
     * @param ResponseInterface $response
     * @param int $status
     */
    public function status(ResponseInterface $response, int $status): ResponseInterface
    {
        return $response->withStatus($status);
    }

    /**
     * Best-effort MIME detection.
     *
     * @param string $path
     */
    private function detectMime(string $path): ?string
    {
        if (class_exists(\finfo::class)) {
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($path) ?: null;
            return is_string($mime) ? $mime : null;
        }
        // Fallback by extension (minimal).
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'txt' => 'text/plain',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'html', 'htm' => 'text/html',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            default => null,
        };
    }
}
