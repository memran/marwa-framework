<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

interface MCPServerInterface
{
    public function registerTool(ToolInterface $tool): self;

    public function registerResource(ResourceInterface $resource): self;

    public function registerPrompt(PromptInterface $prompt): self;

    public function tools(): array;

    public function resources(): array;

    public function prompts(): array;

    public function callTool(string $name, array $arguments = []): ToolResult;

    public function readResource(string $uri): ResourceResult;

    public function getPrompt(string $name, array $arguments = []): PromptResult;

    public function serve(string $transport = 'stdio'): void;

    public function configuration(): array;
}