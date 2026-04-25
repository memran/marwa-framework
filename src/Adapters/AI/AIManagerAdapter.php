<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\AI;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\AIManagerInterface;
use Marwa\Framework\Supports\Config;
use Marwa\AI\AIManager;
use function Marwa\AI\ai;

final class AIManagerAdapter implements AIManagerInterface
{
    private ?AIManager $aiManager = null;

    public function __construct(
        private Application $app,
        private Config $config
    ) {}

    private function getAiManager(): AIManager
    {
        if ($this->aiManager === null) {
            $this->aiManager = ai();
        }

        return $this->aiManager;
    }

    public function complete(string $prompt, array $options = []): mixed
    {
        $response = $this->getAiManager()->complete($prompt, $options);

        if ($response instanceof \Marwa\AI\Response\CompletionResponse) {
            return $response->getContent();
        }

        return $response;
    }

    public function conversation(array $messages = []): mixed
    {
        return $this->getAiManager()->conversation($messages);
    }

    public function embed(array $texts, array $options = []): mixed
    {
        return $this->getAiManager()->embed($texts, $options);
    }

    public function image(string $prompt, array $options = []): mixed
    {
        return $this->getAiManager()->image($prompt, $options);
    }

    public function stream(string $prompt, callable $onChunk, array $options = []): void
    {
        $this->getAiManager()->stream($prompt, $onChunk, $options);
    }

    public function chat(): mixed
    {
        return $this->getAiManager()->chat();
    }

    public function tool($tool): self
    {
        $this->getAiManager()->tool($tool);

        return $this;
    }

    public function getTools(): array
    {
        return $this->getAiManager()->getTools();
    }

    public function providers(): array
    {
        return $this->getAiManager()->providers();
    }

    public function driver(?string $name = null): self
    {
        $this->getAiManager()->driver($name);

        return $this;
    }

    public function configuration(): array
    {
        return [];
    }
}