<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\MarwaAI;
use Marwa\AI\Support\AIResponse;
use Marwa\AI\Support\EmbeddingResponse;
use Marwa\AI\Support\ImageResponse;
use Marwa\AI\Support\Usage;
use Marwa\Framework\Adapters\AI\AIManagerAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\AIManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class AIIntegrationTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->handlersBooted = false;
        $this->basePath = sys_get_temp_dir() . '/marwa-ai-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);

        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents($this->basePath . '/config/ai.php', <<<'PHP'
<?php

return [
    'default' => 'ollama',
    'providers' => [
        'ollama' => [
            'model' => 'test-model',
        ],
    ],
];
PHP);

        MarwaAI::initialize([]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );

        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }

        MarwaAI::initialize([]);
    }

    public function testAiManagerBindingLoadsFrameworkConfig(): void
    {
        $app = new Application($this->basePath);

        $manager = $app->make(AIManagerInterface::class);

        self::assertInstanceOf(AIManagerAdapter::class, $manager);
        self::assertSame('ollama', \Marwa\AI\ai()->getDefaultProvider());
        self::assertSame('ollama', $manager->configuration()['default']);
        self::assertSame('test-model', $manager->configuration()['providers']['ollama']['model']);
    }

    public function testAiHelperUsesRegisteredFakeProvider(): void
    {
        $app = $this->bootstrapApp();
        $app->make(AIManagerInterface::class);
        $this->registerFakeProvider();

        self::assertSame('fake completion: Hello framework', ai_complete('Hello framework', [
            'provider' => 'fake',
        ]));
        self::assertSame('fake', ai()->driver('fake')->getProvider());
    }

    public function testAiCompleteCommandUsesRegisteredFakeProvider(): void
    {
        $app = $this->bootstrapApp();
        $app->make(AIManagerInterface::class);
        $this->registerFakeProvider();

        $console = $app->console()->application();
        $this->handlersBooted = true;
        self::assertTrue($console->has('ai:complete'));
        self::assertTrue($console->has('ai:providers'));
        $tester = new CommandTester($console->find('ai:complete'));

        self::assertSame(0, $tester->execute([
            'prompt' => 'Hello framework',
            '--provider' => 'fake',
        ]));
        self::assertStringContainsString('fake completion: Hello framework', $tester->getDisplay());
    }

    private function bootstrapApp(): Application
    {
        return new Application($this->basePath);
    }

    private function registerFakeProvider(): void
    {
        $manager = MarwaAI::instance();
        $manager->extend('fake', fn (array $config): AIClientInterface => $this->fakeClient($config));
        $manager->configure('fake', ['model' => 'test-model']);
        $manager->setDefaultProvider('fake');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function fakeClient(array $config): AIClientInterface
    {
        return new class ($config) implements AIClientInterface {
            /**
             * @param array<string, mixed> $config
             */
            public function __construct(private array $config) {}

            /**
             * @param list<array<string, mixed>> $messages
             * @param array<string, mixed> $options
             */
            public function completion(array $messages, array $options = []): \Marwa\AI\Contracts\AIResponseInterface
            {
                $content = 'fake completion: ' . (string) ($messages[0]['content'] ?? '');

                return new AIResponse(
                    $content,
                    new Usage(1, 1, $this->getProvider(), $this->getModel()),
                    $this->getModel()
                );
            }

            /**
             * @param list<array<string, mixed>> $messages
             * @param array<string, mixed> $options
             */
            public function streamCompletion(array $messages, array $options = []): \Generator
            {
                yield from [];
            }

            /**
             * @param list<string> $texts
             * @param array<string, mixed> $options
             */
            public function embed(array $texts, array $options = []): \Marwa\AI\Contracts\EmbeddingResponseInterface
            {
                return new EmbeddingResponse(
                    [[0.1, 0.2, 0.3]],
                    $this->getModel(),
                    new Usage(1, 0, $this->getProvider(), $this->getModel())
                );
            }

            /**
             * @param array<string, mixed> $options
             */
            public function generateImage(string $prompt, array $options = []): \Marwa\AI\Contracts\ImageResponseInterface
            {
                return new ImageResponse(
                    ['data' => [['url' => 'https://example.test/' . rawurlencode($prompt)]]],
                    $this->getModel(),
                    new Usage(1, 0, $this->getProvider(), $this->getModel())
                );
            }

            /**
             * @param array<string, mixed> $options
             */
            public function analyzeImage(string $imagePath, string $prompt, array $options = []): \Marwa\AI\Contracts\AIResponseInterface
            {
                return new AIResponse(
                    'analyzed: ' . basename($imagePath) . ' | ' . $prompt,
                    new Usage(1, 1, $this->getProvider(), $this->getModel()),
                    $this->getModel()
                );
            }

            public function countTokens(string $text): int
            {
                return mb_strlen($text);
            }

            public function supports(string $feature): bool
            {
                return in_array($feature, ['completion', 'chat', 'embedding', 'image'], true);
            }

            public function getProvider(): string
            {
                return 'fake';
            }

            public function getModel(): string
            {
                return (string) ($this->config['model'] ?? 'test-model');
            }
        };
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $current = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($current)) {
                $this->removeDirectory($current);
                continue;
            }

            @unlink($current);
        }

        @rmdir($path);
    }
}
