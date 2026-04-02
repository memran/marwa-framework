<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'config:clear', description: 'Remove the cached config file.')]
final class ConfigClearCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = BootstrapConfig::defaults($this->app())['configCache'];

        if (is_file($file)) {
            unlink($file);
        }

        $output->writeln(sprintf('<info>Config cache cleared:</info> %s', $file));

        return Command::SUCCESS;
    }
}
