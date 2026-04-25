<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

interface PromptInterface
{
    public function name(): string;

    public function description(): string;

    public function arguments(): array;

    public function get(array $arguments = []): PromptResult;
}