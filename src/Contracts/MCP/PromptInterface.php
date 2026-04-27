<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\MCP;

interface PromptInterface
{
    public function name(): string;

    public function description(): string;

    /**
     * @return list<array<string, mixed>>
     */
    public function arguments(): array;

    /**
     * @param array<string, mixed> $arguments
     */
    public function get(array $arguments = []): PromptResult;
}
