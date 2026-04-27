<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\MCP;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\MCP\MCPServerInterface;
use Marwa\Framework\Contracts\MCP\PromptInterface;
use Marwa\Framework\Contracts\MCP\PromptResult;
use Marwa\Framework\Contracts\MCP\ResourceInterface;
use Marwa\Framework\Contracts\MCP\ResourceResult;
use Marwa\Framework\Contracts\MCP\ToolInterface;
use Marwa\Framework\Contracts\MCP\ToolResult;
use Marwa\Framework\Supports\Config;
use Memran\MarwaMcp\Prompt\PromptResult as VendorPromptResult;
use Memran\MarwaMcp\Resource\ResourceResult as VendorResourceResult;
use Memran\MarwaMcp\Security\AllowAllPermissionPolicy;
use Memran\MarwaMcp\Server\JsonRpcHandler;
use Memran\MarwaMcp\Server\McpServer;
use Memran\MarwaMcp\Server\ServerFactory;
use Memran\MarwaMcp\Transport\HttpTransport;
use Memran\MarwaMcp\Transport\StdioTransport;

final class MCPAdapter implements MCPServerInterface
{
    private ?McpServer $server = null;

    /**
     * @var array<string, ToolInterface>
     */
    private array $tools = [];

    /**
     * @var array<string, ResourceInterface>
     */
    private array $resources = [];

    /**
     * @var array<string, PromptInterface>
     */
    private array $prompts = [];

    public function __construct(
        private Config $config
    ) {}

    private function getServer(): McpServer
    {
        if ($this->server instanceof McpServer) {
            return $this->server;
        }

        $config = $this->configuration();
        $this->server = ServerFactory::createDefault(
            permissionPolicy: new AllowAllPermissionPolicy(),
            name: (string) ($config['name'] ?? 'marwa-mcp'),
            version: (string) ($config['version'] ?? '1.0.0')
        );

        return $this->server;
    }

    public function registerTool(ToolInterface $tool): self
    {
        $wrapper = new class ($tool) implements \Memran\MarwaMcp\Tool\ToolInterface {
            public function __construct(private ToolInterface $tool) {}

            public function name(): string
            {
                return $this->tool->name();
            }

            public function description(): string
            {
                return $this->tool->description();
            }

            /**
             * @return array<string, mixed>
             */
            public function schema(): array
            {
                return $this->tool->schema();
            }

            /**
             * @param array<string, mixed> $arguments
             */
            public function call(array $arguments): \Memran\MarwaMcp\Tool\ToolResult
            {
                $result = $this->tool->execute($arguments);

                return \Memran\MarwaMcp\Tool\ToolResult::text(
                    $result->getContent(),
                    $result->isError()
                );
            }
        };

        $this->tools[$tool->name()] = $tool;
        $this->getServer()->tools()->register($wrapper);

        return $this;
    }

    public function registerResource(ResourceInterface $resource): self
    {
        $wrapper = new class ($resource) implements \Memran\MarwaMcp\Resource\ResourceInterface {
            public function __construct(private ResourceInterface $resource) {}

            public function uri(): string
            {
                return $this->resource->uri();
            }

            public function name(): string
            {
                return $this->resource->name();
            }

            public function description(): string
            {
                return $this->resource->description();
            }

            public function read(): \Memran\MarwaMcp\Resource\ResourceResult
            {
                $result = $this->resource->read();

                return new VendorResourceResult(
                    $result->getUri(),
                    $result->getContent(),
                    $result->getMimeType()
                );
            }
        };

        $this->resources[$resource->uri()] = $resource;
        $this->getServer()->resources()->register($wrapper);

        return $this;
    }

    public function registerPrompt(PromptInterface $prompt): self
    {
        $wrapper = new class ($prompt) implements \Memran\MarwaMcp\Prompt\PromptInterface {
            public function __construct(private PromptInterface $prompt) {}

            public function name(): string
            {
                return $this->prompt->name();
            }

            public function description(): string
            {
                return $this->prompt->description();
            }

            /**
             * @return list<array<string, mixed>>
             */
            public function arguments(): array
            {
                return $this->prompt->arguments();
            }

            /**
             * @param array<string, mixed> $arguments
             */
            public function get(array $arguments = []): \Memran\MarwaMcp\Prompt\PromptResult
            {
                $result = $this->prompt->get($arguments);

                return new VendorPromptResult(
                    $this->prompt->description(),
                    $result->getMessages()
                );
            }
        };

        $this->prompts[$prompt->name()] = $prompt;
        $this->getServer()->prompts()->register($wrapper);

        return $this;
    }

    /**
     * @return array<string, ToolInterface>
     */
    public function tools(): array
    {
        return $this->tools;
    }

    /**
     * @return array<string, ResourceInterface>
     */
    public function resources(): array
    {
        return $this->resources;
    }

    /**
     * @return array<string, PromptInterface>
     */
    public function prompts(): array
    {
        return $this->prompts;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function callTool(string $name, array $arguments = []): ToolResult
    {
        if (!isset($this->tools[$name])) {
            return ToolResult::error("Tool [{$name}] not found");
        }

        return $this->tools[$name]->execute($arguments);
    }

    public function readResource(string $uri): ResourceResult
    {
        if (!isset($this->resources[$uri])) {
            return ResourceResult::create($uri, '', 'text/plain');
        }

        return $this->resources[$uri]->read();
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function getPrompt(string $name, array $arguments = []): PromptResult
    {
        if (!isset($this->prompts[$name])) {
            return PromptResult::userText("Prompt [{$name}] not found");
        }

        return $this->prompts[$name]->get($arguments);
    }

    public function serve(string $transport = 'stdio'): void
    {
        $handler = new JsonRpcHandler($this->getServer());

        match ($transport) {
            'stdio' => (new StdioTransport($handler))->listen(),
            'http' => (new HttpTransport($handler))->listen(),
            default => throw new \InvalidArgumentException("Unsupported transport: {$transport}"),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        $this->config->loadIfExists('mcp.php');

        return array_merge([
            'name' => 'marwa-mcp',
            'version' => '1.0.0',
            'transport' => 'stdio',
            'port' => 8080,
        ], $this->config->getArray('mcp', []));
    }
}
