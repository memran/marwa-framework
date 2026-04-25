<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

interface ToolInterface
{
    public function name(): string;

    public function description(): string;

    public function schema(): array;

    public function execute(array $arguments): ToolResult;
}