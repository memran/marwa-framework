<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface AIManagerInterface
{
    public function complete(string $prompt, array $options = []): mixed;

    public function conversation(array $messages = []): mixed;

    public function embed(array $texts, array $options = []): mixed;

    public function image(string $prompt, array $options = []): mixed;

    public function stream(string $prompt, callable $onChunk, array $options = []): void;

    public function chat(): mixed;

    public function tool($tool): self;

    public function getTools(): array;

    public function providers(): array;

    public function driver(?string $name = null): self;

    public function configuration(): array;
}