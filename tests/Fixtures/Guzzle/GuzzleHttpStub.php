<?php

declare(strict_types=1);

namespace GuzzleHttp {
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\StreamInterface;

    if (!interface_exists(ClientInterface::class)) {
        interface ClientInterface
        {
            /**
             * @param array<string, mixed> $options
             */
            public function request(string $method, string $uri = '', array $options = []): ResponseInterface;

            /**
             * @param array<string, mixed> $options
             */
            public function send(RequestInterface $request, array $options = []): ResponseInterface;

            public function getConfig(?string $option = null): mixed;
        }
    }

    if (!class_exists(Client::class)) {
        final class Client implements ClientInterface
        {
            /**
             * @var array<string, mixed>
             */
            private array $config;

            /**
             * @var list<array{method: string, uri: string, options: array<string, mixed>}>
             */
            public array $requests = [];

            /**
             * @param array<string, mixed> $config
             */
            public function __construct(array $config = [])
            {
                $this->config = $config;
            }

            /**
             * @param array<string, mixed> $options
             */
            public function request(string $method, string $uri = '', array $options = []): ResponseInterface
            {
                $this->requests[] = [
                    'method' => strtoupper($method),
                    'uri' => $uri,
                    'options' => $options,
                ];

                $body = sprintf('%s %s', strtoupper($method), $uri);

                return new Response(200, ['X-Request-Method' => strtoupper($method)], $body);
            }

            /**
             * @param array<string, mixed> $options
             */
            public function send(RequestInterface $request, array $options = []): ResponseInterface
            {
                $this->requests[] = [
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                    'options' => $options,
                ];

                return new Response(200, ['X-Request-Method' => $request->getMethod()], (string) $request->getBody());
            }

            public function getConfig(?string $option = null): mixed
            {
                if ($option === null) {
                    return $this->config;
                }

                return $this->config[$option] ?? null;
            }
        }
    }

    if (!class_exists(Response::class)) {
        final class Response implements ResponseInterface
        {
            /**
             * @var array<string, list<string>>
             */
            private array $headers = [];

            private StreamInterface $body;

            /**
             * @param array<string, string|list<string>> $headers
             */
            public function __construct(
                private int $statusCode = 200,
                array $headers = [],
                string $body = ''
            ) {
                foreach ($headers as $name => $value) {
                    $this->headers[$this->normalizeHeaderName((string) $name)] = is_array($value)
                        ? array_map('strval', $value)
                        : [(string) $value];
                }

                $this->body = new Stream($body);
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
            {
                $clone = clone $this;
                $clone->statusCode = $code;

                return $clone;
            }

            public function getReasonPhrase(): string
            {
                return '';
            }

            public function getProtocolVersion(): string
            {
                return '1.1';
            }

            public function withProtocolVersion(string $version): ResponseInterface
            {
                return clone $this;
            }

            public function getHeaders(): array
            {
                return $this->headers;
            }

            public function hasHeader(string $name): bool
            {
                return isset($this->headers[$this->normalizeHeaderName($name)]);
            }

            public function getHeader(string $name): array
            {
                return $this->headers[$this->normalizeHeaderName($name)] ?? [];
            }

            public function getHeaderLine(string $name): string
            {
                return implode(', ', $this->getHeader($name));
            }

            public function withHeader(string $name, $value): ResponseInterface
            {
                $clone = clone $this;
                $clone->headers[$this->normalizeHeaderName($name)] = is_array($value) ? array_map('strval', $value) : [(string) $value];

                return $clone;
            }

            public function withAddedHeader(string $name, $value): ResponseInterface
            {
                $clone = clone $this;
                $header = $this->normalizeHeaderName($name);
                $values = is_array($value) ? array_map('strval', $value) : [(string) $value];
                $clone->headers[$header] = array_merge($clone->headers[$header] ?? [], $values);

                return $clone;
            }

            public function withoutHeader(string $name): ResponseInterface
            {
                $clone = clone $this;
                unset($clone->headers[$this->normalizeHeaderName($name)]);

                return $clone;
            }

            public function getBody(): StreamInterface
            {
                return $this->body;
            }

            public function withBody(StreamInterface $body): ResponseInterface
            {
                $clone = clone $this;
                $clone->body = $body;

                return $clone;
            }

            private function normalizeHeaderName(string $name): string
            {
                return strtolower($name);
            }
        }
    }

    if (!class_exists(Stream::class)) {
        final class Stream implements StreamInterface
        {
            private ?string $buffer;
            private int $position = 0;

            public function __construct(string $buffer = '')
            {
                $this->buffer = $buffer;
            }

            public function __toString(): string
            {
                return $this->buffer;
            }

            public function close(): void
            {
                $this->buffer = null;
            }

            public function detach()
            {
                $this->buffer = null;

                return null;
            }

            public function getSize(): ?int
            {
                return $this->buffer === null ? null : strlen($this->buffer);
            }

            public function tell(): int
            {
                return $this->position;
            }

            public function eof(): bool
            {
                return $this->buffer === null || $this->position >= strlen($this->buffer);
            }

            public function isSeekable(): bool
            {
                return true;
            }

            public function seek(int $offset, int $whence = SEEK_SET): void
            {
                if ($this->buffer === null) {
                    $this->position = 0;

                    return;
                }

                $length = strlen($this->buffer);
                $position = match ($whence) {
                    SEEK_CUR => $this->position + $offset,
                    SEEK_END => $length + $offset,
                    default => $offset,
                };
                $this->position = max(0, min($length, $position));
            }

            public function rewind(): void
            {
                $this->position = 0;
            }

            public function isWritable(): bool
            {
                return true;
            }

            public function write(string $string): int
            {
                $this->buffer = ($this->buffer ?? '') . $string;
                $this->position = strlen($this->buffer);

                return strlen($string);
            }

            public function isReadable(): bool
            {
                return true;
            }

            public function read(int $length): string
            {
                $buffer = $this->buffer ?? '';
                $chunk = substr($buffer, $this->position, $length);
                $this->position += strlen($chunk);

                return $chunk;
            }

            public function getContents(): string
            {
                $buffer = $this->buffer ?? '';
                $contents = substr($buffer, $this->position);
                $this->position = strlen($buffer);

                return $contents;
            }

            public function getMetadata(?string $key = null): mixed
            {
                $metadata = [
                    'uri' => 'php://memory',
                    'seekable' => true,
                    'readable' => true,
                    'writable' => true,
                ];

                return $key === null ? $metadata : ($metadata[$key] ?? null);
            }
        }
    }
}
