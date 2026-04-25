<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

final class PromptResult
{
    private array $messages = [];

    public static function userText(string $content): self
    {
        $instance = new self();
        $instance->messages[] = [
            'role' => 'user',
            'content' => $content,
        ];

        return $instance;
    }

    public function addSystem(string $content): self
    {
        $this->messages[] = [
            'role' => 'system',
            'content' => $content,
        ];

        return $this;
    }

    public function addUser(string $content): self
    {
        $this->messages[] = [
            'role' => 'user',
            'content' => $content,
        ];

        return $this;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function toArray(): array
    {
        return $this->messages;
    }
}