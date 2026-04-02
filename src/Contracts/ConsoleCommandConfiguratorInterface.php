<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Marwa\Framework\Console\CommandRegistry;

interface ConsoleCommandConfiguratorInterface
{
    public function registerCommands(CommandRegistry $registry): void;
}
