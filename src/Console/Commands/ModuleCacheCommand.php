<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\ModuleConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'module:cache', description: 'Build the module manifest cache when marwa-module is installed.')]
final class ModuleCacheCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists(\Marwa\Module\ModuleRepository::class)) {
            $output->writeln('<comment>marwa-module is not installed. Skipping.</comment>');

            return Command::SUCCESS;
        }

        $this->config()->loadIfExists(ModuleConfig::KEY . '.php');
        $config = array_replace_recursive(ModuleConfig::defaults($this->app()), $this->config()->getArray(ModuleConfig::KEY, []));

        $repository = new \Marwa\Module\ModuleRepository($config['paths'], $config['cache']);
        $modules = $repository->all(true);

        $output->writeln(sprintf('<info>Module cache created:</info> %s', $config['cache']));
        $output->writeln(sprintf('<info>Discovered modules:</info> %d', count($modules)));

        return Command::SUCCESS;
    }
}
