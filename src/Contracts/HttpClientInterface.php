<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface HttpClientInterface
{
    /**
     * @return array{
     *     enabled: bool,
     *     default: string,
     *     clients: array<string, array<string, mixed>>
     * }
     */
    public function configuration(): array;

    public function client(?string $name = null): ClientInterface;

    public function withClient(string $name): self;

    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): self;

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self;

    public function header(string $name, string $value): self;

    public function token(string $token, string $type = 'Bearer'): self;

    public function baseUri(string $uri): self;

    public function timeout(int|float $seconds): self;

    public function connectTimeout(int|float $seconds): self;

    public function verify(bool|string $verify): self;

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $uri = '', array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function get(string $uri = '', array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $uri = '', array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function put(string $uri = '', array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function patch(string $uri = '', array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function delete(string $uri = '', array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function head(string $uri = '', array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function options(string $uri = '', array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $options
     */
    public function json(string $method, string $uri = '', array $payload = [], array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $options
     */
    public function form(string $method, string $uri = '', array $payload = [], array $options = []): ResponseInterface;

    /**
     * @param array<int, array<string, mixed>> $parts
     * @param array<string, mixed> $options
     */
    public function multipart(string $method, string $uri = '', array $parts = [], array $options = []): ResponseInterface;
}
