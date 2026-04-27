<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface AIManagerInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function complete(string $prompt, array $options = []): mixed;

    /**
     * @param list<array<string, mixed>>|array<string, mixed> $messages
     */
    public function conversation(array $messages = []): mixed;

    /**
     * @param list<string> $texts
     * @param array<string, mixed> $options
     */
    public function embed(array $texts, array $options = []): mixed;

    /**
     * @param array<string, mixed> $options
     */
    public function image(string $prompt, array $options = []): mixed;

    /**
     * @param array<string, mixed> $options
     */
    public function stream(string $prompt, callable $onChunk, array $options = []): void;

    public function chat(): mixed;

    /**
     * @param mixed $tool
     */
    public function tool($tool): self;

    /**
     * @return array<string, mixed>
     */
    public function getTools(): array;

    /**
     * @return list<string>
     */
    public function providers(): array;

    public function driver(?string $name = null): self;

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array;
}
