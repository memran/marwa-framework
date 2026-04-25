<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

final class ResourceResult
{
    public function __construct(
        private string $uri,
        private string $content,
        private string $mimeType = 'text/plain'
    ) {}

    public static function create(string $uri, string $content, string $mimeType = 'text/plain'): self
    {
        return new self($uri, $content, $mimeType);
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'mimeType' => $this->mimeType,
            'content' => $this->content,
        ];
    }
}