<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface AIToolInterface
{
    public function name(): string;

    public function description(): string;

    /**
     * @return array<string, mixed>
     */
    public function schema(): array;

    /**
     * @param array<string, mixed> $args
     */
    public function execute(array $args): string;
}
