<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'bootstrap:cache', description: 'Build config, route, and module bootstrap caches.')]
final class BootstrapCacheCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ([
            ConfigCacheCommand::class,
            RouteCacheCommand::class,
            ModuleCacheCommand::class,
        ] as $commandClass) {
            $command = $this->container()->get($commandClass);

            if ($command instanceof \Marwa\Framework\Console\AbstractCommand) {
                $command->setMarwaApplication($this->app());
            }

            $command->run($input, $output);
        }

        return Command::SUCCESS;
    }
}
