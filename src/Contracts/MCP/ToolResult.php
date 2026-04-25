<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

final class ToolResult
{
    public function __construct(
        private string $content,
        private bool $isError = false
    ) {}

    public static function text(string $content): self
    {
        return new self($content, false);
    }

    public static function error(string $content): self
    {
        return new self($content, true);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function isError(): bool
    {
        return $this->isError;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'isError' => $this->isError,
        ];
    }
}