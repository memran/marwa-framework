<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\ModuleConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'module:clear', description: 'Remove the module manifest cache file.')]
final class ModuleClearCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->config()->loadIfExists(ModuleConfig::KEY . '.php');
        $config = array_replace_recursive(ModuleConfig::defaults($this->app()), $this->config()->getArray(ModuleConfig::KEY, []));

        if (is_file($config['cache'])) {
            unlink($config['cache']);
        }

        $output->writeln(sprintf('<info>Module cache cleared:</info> %s', $config['cache']));

        return Command::SUCCESS;
    }
}
