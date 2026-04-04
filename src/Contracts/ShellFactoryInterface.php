<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Marwa\Framework\Application;

interface ShellFactoryInterface
{
    public function available(): bool;

    /**
     * @param array<string, mixed> $variables
     */
    public function run(Application $app, array $variables = []): int;
}
