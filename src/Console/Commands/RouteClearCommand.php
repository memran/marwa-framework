<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'route:clear', description: 'Remove the compiled route cache file.')]
final class RouteClearCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = BootstrapConfig::defaults($this->app())['routeCache'];

        if (is_file($file)) {
            unlink($file);
        }

        $output->writeln(sprintf('<info>Route cache cleared:</info> %s', $file));

        return Command::SUCCESS;
    }
}
