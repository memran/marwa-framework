<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ai:providers', description: 'List available AI providers')]
final class AiProvidersCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setDescription('List available AI providers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ai = $this->app()->make(\Marwa\Framework\Contracts\AIManagerInterface::class);
        $providers = $ai->providers();

        $output->writeln('<info>Available AI providers:</info>');
        
        foreach ($providers as $provider) {
            $output->writeln("  - {$provider}");
        }

        return Command::SUCCESS;
    }
}