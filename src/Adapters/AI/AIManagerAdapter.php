<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\AI;

use Marwa\Framework\Contracts\AIManagerInterface;
use Marwa\Framework\Supports\Config;
use Marwa\AI\Contracts\AIManagerInterface as VendorAIManagerInterface;
use function Marwa\AI\ai;
use function Marwa\AI\chat;
use function Marwa\AI\complete;
use function Marwa\AI\conversation;
use function Marwa\AI\embed;
use function Marwa\AI\image;
use function Marwa\AI\stream;

final class AIManagerAdapter implements AIManagerInterface
{
    public function __construct(
        private Config $config
    ) {}

    /**
     * @return VendorAIManagerInterface
     */
    private function getAiManager(): VendorAIManagerInterface
    {
        /** @var VendorAIManagerInterface $manager */
        $manager = ai();

        return $manager;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function complete(string $prompt, array $options = []): mixed
    {
        return complete($prompt, $options)->getContent();
    }

    /**
     * @param list<array<string, mixed>>|array<string, mixed> $messages
     */
    public function conversation(array $messages = []): mixed
    {
        return conversation($messages);
    }

    /**
     * @param list<string> $texts
     * @param array<string, mixed> $options
     */
    public function embed(array $texts, array $options = []): mixed
    {
        return embed($texts, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function image(string $prompt, array $options = []): mixed
    {
        return image($prompt, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function stream(string $prompt, callable $onChunk, array $options = []): void
    {
        stream($prompt, $onChunk, $options);
    }

    public function chat(): mixed
    {
        return chat();
    }

    /**
     * @param mixed $tool
     */
    public function tool($tool): self
    {
        $this->getAiManager()->tool($tool);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTools(): array
    {
        return $this->getAiManager()->getTools();
    }

    /**
     * @return list<string>
     */
    public function providers(): array
    {
        return $this->getAiManager()->getAvailableProviders();
    }

    public function driver(?string $name = null): self
    {
        $this->getAiManager()->driver($name);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        $this->config->loadIfExists('ai.php');

        return $this->config->getArray('ai', []);
    }
}
