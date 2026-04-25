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
use Memran\MarwaMcp\McpServer;
use Memran\MarwaMcp\Tool\ToolRegistry;
use Memran\MarwaMcp\Resource\ResourceRegistry;
use Memran\MarwaMcp\Prompt\PromptRegistry;
use Memran\MarwaMcp\ServerFactory;
use Memran\MarwaMcp\Security\AllowAllPermissionPolicy;
use Memran\MarwaMcp\Transport\StdioTransport;
use Memran\MarwaMcp\Transport\HttpTransport;

final class MCPAdapter implements MCPServerInterface
{
    private ?McpServer $server = null;
    private array $tools = [];
    private array $resources = [];
    private array $prompts = [];

    public function __construct(
        private Application $app,
        private Config $config
    ) {}

    private function getServer(): McpServer
    {
        if ($this->server === null) {
            $config = $this->configuration();
            
            $this->server = ServerFactory::createDefault(
                name: $config['name'] ?? 'marwa-mcp',
                version: $config['version'] ?? '1.0.0'
            );
        }

        return $this->server;
    }

    public function registerTool(ToolInterface $tool): self
    {
        $wrapper = new class($tool) implements \Memran\MarwaMcp\Tool\ToolInterface {
            public function __construct(private ToolInterface $tool) {}

            public function name(): string
            {
                return $this->tool->name();
            }

            public function description(): string
            {
                return $this->tool->description();
            }

            public function schema(): array
            {
                return $this->tool->schema();
            }

            public function call(array $arguments): mixed
            {
                $result = $this->tool->execute($arguments);
                return $result->getContent();
            }
        };

        $this->tools[$tool->name()] = $tool;
        $this->getServer()->getTools()->register($wrapper);

        return $this;
    }

    public function registerResource(ResourceInterface $resource): self
    {
        $wrapper = new class($resource) implements \Memran\MarwaMcp\Resource\ResourceInterface {
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

            public function mimeType(): string
            {
                return $this->resource->mimeType();
            }

            public function read(): mixed
            {
                $result = $this->resource->read();
                return new \Memran\MarwaMcp\Resource\ResourceResult(
                    $result->getUri(),
                    $result->getContent(),
                    $result->getMimeType()
                );
            }
        };

        $this->resources[$resource->uri()] = $resource;
        $this->getServer()->getResources()->register($wrapper);

        return $this;
    }

    public function registerPrompt(PromptInterface $prompt): self
    {
        $wrapper = new class($prompt) implements \Memran\MarwaMcp\Prompt\PromptInterface {
            public function __construct(private PromptInterface $prompt) {}

            public function name(): string
            {
                return $this->prompt->name();
            }

            public function description(): string
            {
                return $this->prompt->description();
            }

            public function arguments(): array
            {
                return $this->prompt->arguments();
            }

            public function get(array $arguments = []): mixed
            {
                $result = $this->prompt->get($arguments);
                $messages = $result->getMessages();
                return \Memran\MarwaMcp\Prompt\PromptResult::create($messages);
            }
        };

        $this->prompts[$prompt->name()] = $prompt;
        $this->getServer()->getPrompts()->register($wrapper);

        return $this;
    }

    public function tools(): array
    {
        return $this->tools;
    }

    public function resources(): array
    {
        return $this->resources;
    }

    public function prompts(): array
    {
        return $this->prompts;
    }

    public function callTool(string $name, array $arguments = []): ToolResult
    {
        if (!isset($this->tools[$name])) {
            return ToolResult::error("Tool [{$name}] not found");
        }

        $tool = $this->tools[$name];
        $result = $tool->execute($arguments);

        return $result;
    }

    public function readResource(string $uri): ResourceResult
    {
        if (!isset($this->resources[$uri])) {
            return ResourceResult::create($uri, '', 'text/plain');
        }

        $resource = $this->resources[$uri];

        return $resource->read();
    }

    public function getPrompt(string $name, array $arguments = []): PromptResult
    {
        if (!isset($this->prompts[$name])) {
            return PromptResult::userText("Prompt [{$name}] not found");
        }

        $prompt = $this->prompts[$name];

        return $prompt->get($arguments);
    }

    public function serve(string $transport = 'stdio'): void
    {
        $server = $this->getServer();
        $handler = new \Memran\MarwaMcp\JsonRpcHandler($server);

        match ($transport) {
            'stdio' => (new StdioTransport($handler))->listen(),
            'http' => (new HttpTransport($handler))->listen(),
            default => throw new \InvalidArgumentException("Unsupported transport: {$transport}"),
        };
    }

    public function configuration(): array
    {
        $configData = $this->config->getArray('mcp', []);

        return array_merge([
            'name' => 'marwa-mcp',
            'version' => '1.0.0',
            'transport' => 'stdio',
            'port' => 8080,
        ], $configData);
    }
}