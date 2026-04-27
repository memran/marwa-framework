<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ai:complete', description: 'Generate AI text completion')]
final class AiCompleteCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('prompt', InputArgument::REQUIRED, 'The prompt to complete')
            ->addOption('provider', 'p', InputOption::VALUE_OPTIONAL, 'AI provider to use')
            ->addOption('model', 'm', InputOption::VALUE_OPTIONAL, 'Model to use')
            ->addOption('temperature', 't', InputOption::VALUE_OPTIONAL, 'Temperature (0-2)')
            ->addOption('max-tokens', null, InputOption::VALUE_OPTIONAL, 'Max tokens');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prompt = (string) $input->getArgument('prompt');
        $provider = $input->getOption('provider');

        $options = [];

        if ($model = $input->getOption('model')) {
            $options['model'] = $model;
        }
        if ($temperature = $input->getOption('temperature')) {
            $options['temperature'] = (float) $temperature;
        }
        if ($maxTokens = $input->getOption('max-tokens')) {
            $options['max_tokens'] = (int) $maxTokens;
        }

        if ($provider) {
            $this->app()->make(\Marwa\Framework\Contracts\AIManagerInterface::class)->driver($provider);
        }

        $ai = $this->app()->make(\Marwa\Framework\Contracts\AIManagerInterface::class);
        $result = $ai->complete($prompt, $options);

        if (is_string($result)) {
            $output->writeln($result);
        } else {
            $output->writeln((string) $result);
        }

        return Command::SUCCESS;
    }
}
