<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Adapters\MCP\MCPAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\MCP\MCPServerInterface;
use Marwa\Framework\Contracts\MCP\ResourceInterface;
use Marwa\Framework\Contracts\MCP\ResourceResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class MCPIntegrationTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->handlersBooted = false;
        $this->basePath = sys_get_temp_dir() . '/marwa-mcp-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);

        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents($this->basePath . '/config/mcp.php', <<<'PHP'
<?php

return [
    'name' => 'Test MCP',
    'version' => '9.9.9',
    'transport' => 'stdio',
    'port' => 9090,
];
PHP);
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
    }

    public function testMcpBindingAndHelperAreRegisteredWhenPackageIsInstalled(): void
    {
        $app = new Application($this->basePath);

        self::assertTrue($app->has(MCPServerInterface::class));

        $mcp = $app->make(MCPServerInterface::class);

        self::assertInstanceOf(MCPAdapter::class, $mcp);
        self::assertSame('Test MCP', $mcp->configuration()['name']);
        self::assertSame('9.9.9', $mcp->configuration()['version']);
        self::assertSame('stdio', $mcp->configuration()['transport']);
        self::assertSame($mcp, mcp());

        $mcp->registerResource(new class () implements ResourceInterface {
            public function uri(): string
            {
                return 'marwa://status';
            }

            public function name(): string
            {
                return 'Status';
            }

            public function description(): string
            {
                return 'Application status.';
            }

            public function mimeType(): string
            {
                return 'application/json';
            }

            public function read(): ResourceResult
            {
                return ResourceResult::create($this->uri(), '{"ok":true}', $this->mimeType());
            }
        });

        self::assertSame('application/json', $mcp->resources()['marwa://status']->mimeType());
    }

    public function testConsoleRegistersMcpServeCommand(): void
    {
        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        self::assertTrue($console->has('mcp:serve'));

        $tester = new CommandTester($console->find('mcp:serve'));

        self::assertSame(Command::INVALID, $tester->execute([
            'transport' => 'invalid',
        ]));
        self::assertStringContainsString('Invalid transport', $tester->getDisplay());
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
