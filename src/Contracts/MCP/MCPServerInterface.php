<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

interface MCPServerInterface
{
    public function registerTool(ToolInterface $tool): self;

    public function registerResource(ResourceInterface $resource): self;

    public function registerPrompt(PromptInterface $prompt): self;

    /**
     * @return array<string, ToolInterface>
     */
    public function tools(): array;

    /**
     * @return array<string, ResourceInterface>
     */
    public function resources(): array;

    /**
     * @return array<string, PromptInterface>
     */
    public function prompts(): array;

    /**
     * @param array<string, mixed> $arguments
     */
    public function callTool(string $name, array $arguments = []): ToolResult;

    public function readResource(string $uri): ResourceResult;

    /**
     * @param array<string, mixed> $arguments
     */
    public function getPrompt(string $name, array $arguments = []): PromptResult;

    public function serve(string $transport = 'stdio'): void;

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array;
}
