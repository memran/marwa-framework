<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface AIToolInterface
{
    public function name(): string;

    public function description(): string;

    public function schema(): array;

    public function execute(array $args): string;
}