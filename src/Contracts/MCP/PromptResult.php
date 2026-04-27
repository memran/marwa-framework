<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

final class PromptResult
{
    /**
     * @var list<array<string, mixed>>
     */
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

    /**
     * @return list<array<string, mixed>>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->messages;
    }
}
