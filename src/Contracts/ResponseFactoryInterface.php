<?php

declare(strict_types=1);

namespace Marwa\App\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

use Marwa\App\Http\Response\Cookie;

/**
 * Interface ResponseFactoryInterface
 *
 * Laravel-style fluent response creator built on Zend/Laminas PSR-7 responses.
 */
interface ResponseFactoryInterface
{
    /**
     * Create a plain/text response.
     *
     * @param string|Stringable $content
     * @param int $status
     * @param array<string,string|string[]> $headers
     */
    public function make(string|\Stringable $content = '', int $status = 200, array $headers = []): ResponseInterface;

    /**
     * Create a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array<string,string|string[]> $headers
     * @param int $options JSON encode flags
     */
    public function json(mixed $data, int $status = 200, array $headers = [], int $options = 0): ResponseInterface;

    /**
     * Create a redirect response.
     *
     * @param string $to
     * @param int $status
     * @param array<string,string|string[]> $headers
     */
    public function redirect(string $to, int $status = 302, array $headers = []): ResponseInterface;

    /**
     * Create a 204/empty response.
     *
     * @param int $status
     * @param array<string,string|string[]> $headers
     */
    public function noContent(int $status = 204, array $headers = []): ResponseInterface;

    /**
     * Stream a response with a user callback that writes to php://output.
     *
     * @param callable():void $callback
     * @param int $status
     * @param array<string,string|string[]> $headers
     */
    public function stream(callable $callback, int $status = 200, array $headers = []): ResponseInterface;

    /**
     * Return a response that serves a local file (inline).
     *
     * @param string $path
     * @param string|null $name Suggested filename
     * @param array<string,string|string[]> $headers
     */
    public function file(string $path, ?string $name = null, array $headers = []): ResponseInterface;

    /**
     * Return a response that forces file download (attachment).
     *
     * @param string $path
     * @param string|null $name
     * @param array<string,string|string[]> $headers
     */
    public function download(string $path, ?string $name = null, array $headers = []): ResponseInterface;

    /**
     * Add/override headers on a response.
     *
     * @param ResponseInterface $response
     * @param array<string,string|string[]> $headers
     */
    public function withHeaders(ResponseInterface $response, array $headers): ResponseInterface;

    /**
     * Attach a cookie on the response.
     *
     * @param ResponseInterface $response
     * @param Cookie $cookie
     */
    public function cookie(ResponseInterface $response, Cookie $cookie): ResponseInterface;

    /**
     * Attach many cookies on the response.
     *
     * @param ResponseInterface $response
     * @param array<int, Cookie> $cookies
     */
    public function withCookies(ResponseInterface $response, array $cookies): ResponseInterface;

    /**
     * Replace the body with a given stream.
     *
     * @param ResponseInterface $response
     * @param StreamInterface $stream
     */
    public function withBody(ResponseInterface $response, StreamInterface $stream): ResponseInterface;

    /**
     * Convenience helper to set HTTP status.
     *
     * @param ResponseInterface $response
     * @param int $status
     */
    public function status(ResponseInterface $response, int $status): ResponseInterface;
}
