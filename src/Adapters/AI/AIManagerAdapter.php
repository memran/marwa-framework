<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\AI;

use function Marwa\AI\ai;
use function Marwa\AI\chat;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\AIManagerInterface as VendorAIManagerInterface;

use function Marwa\AI\embed;
use function Marwa\AI\image;

use Marwa\AI\MarwaAI;

use function Marwa\AI\stream;

use Marwa\Framework\Contracts\AIManagerInterface;
use Marwa\Framework\Supports\Config;

final class AIManagerAdapter implements AIManagerInterface
{
    /**
     * @var array<string, list<string>>
     */
    private const PROVIDER_API_KEY_ENV_VARS = [
        'openai' => ['OPENAI_API_KEY'],
        'anthropic' => ['ANTHROPIC_API_KEY'],
        'google' => ['GOOGLE_API_KEY'],
        'grok' => ['XAI_API_KEY', 'GROK_API_KEY'],
        'xai' => ['XAI_API_KEY', 'GROK_API_KEY'],
        'mistral' => ['MISTRAL_API_KEY'],
        'deepseek' => ['DEEPSEEK_API_KEY'],
    ];

    public function __construct(
        private Config $config
    ) {
        MarwaAI::initialize($this->configuration());
    }

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
        $this->assertProviderIsConfigured($this->resolveProvider($options));

        return $this->conversation($prompt, $options)->send($options)->getContent();
    }

    public function driver(?string $name = null): AIClientInterface
    {
        $provider = $this->resolveProvider([], $name);
        $this->assertProviderIsConfigured($provider);

        /** @var AIClientInterface $driver */
        $driver = ai((string) $provider);

        return $driver;
    }

    /**
     * @param list<array<string, mixed>>|array<string, mixed>|string $messages
     * @param array<string, mixed> $options
     */
    public function conversation(array|string $messages = [], array $options = []): mixed
    {
        $this->assertProviderIsConfigured($this->resolveProvider($options));

        return $this->getAiManager()->conversation($messages, $options);
    }

    /**
     * @param list<string> $texts
     * @param array<string, mixed> $options
     */
    public function embed(array $texts, array $options = []): mixed
    {
        $this->assertProviderIsConfigured($this->resolveProvider($options));

        return embed($texts, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function image(string $prompt, array $options = []): mixed
    {
        $this->assertProviderIsConfigured($this->resolveProvider($options));

        return image($prompt, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function stream(string $prompt, callable $onChunk, array $options = []): void
    {
        $this->assertProviderIsConfigured($this->resolveProvider($options));

        stream($prompt, $onChunk, $options);
    }

    public function chat(): mixed
    {
        $this->assertProviderIsConfigured($this->resolveProvider());

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

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        $this->config->loadIfExists('ai.php');

        return $this->config->getArray('ai', []);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveProvider(array $options = [], ?string $provider = null): string
    {
        $resolved = $provider ?? $options['provider'] ?? $this->configuration()['default'] ?? 'ollama';

        return strtolower((string) $resolved);
    }

    private function assertProviderIsConfigured(string $provider): void
    {
        if (!$this->requiresApiKey($provider)) {
            return;
        }

        if ($this->resolveApiKey($provider) !== null) {
            return;
        }

        $envVars = self::PROVIDER_API_KEY_ENV_VARS[$provider];

        throw new \RuntimeException(sprintf(
            'AI provider [%s] requires an API key before sending requests. Set providers.%s.api_key in config/ai.php or define one of: %s.',
            $provider,
            $provider,
            implode(', ', $envVars)
        ));
    }

    private function requiresApiKey(string $provider): bool
    {
        if (!isset(self::PROVIDER_API_KEY_ENV_VARS[$provider])) {
            return false;
        }

        $config = $this->providerConfiguration($provider);

        return ($config['require_api_key'] ?? true) !== false;
    }

    private function resolveApiKey(string $provider): ?string
    {
        $config = $this->providerConfiguration($provider);
        $apiKey = $config['api_key'] ?? null;

        if (is_string($apiKey) && trim($apiKey) !== '') {
            return trim($apiKey);
        }

        foreach (self::PROVIDER_API_KEY_ENV_VARS[$provider] ?? [] as $envVar) {
            $value = getenv($envVar);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function providerConfiguration(string $provider): array
    {
        $configuration = $this->configuration();
        $providers = $configuration['providers'] ?? [];

        $providerConfig = is_array($providers[$provider] ?? null)
            ? $providers[$provider]
            : ($configuration[$provider] ?? []);

        return is_array($providerConfig) ? $providerConfig : [];
    }
}
