<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

interface ToolInterface
{
    public function name(): string;

    public function description(): string;

    /**
     * @return array<string, mixed>
     */
    public function schema(): array;

    /**
     * @param array<string, mixed> $arguments
     */
    public function execute(array $arguments): ToolResult;
}
