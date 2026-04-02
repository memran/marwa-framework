<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'key:generate', description: 'Generate a cryptographically secure random application key.')]
final class GenerateKeyCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('length', 'l', InputOption::VALUE_REQUIRED, 'Key length in bytes before encoding.', '32')
            ->addOption('raw', null, InputOption::VALUE_NONE, 'Output the raw key string instead of hex.')
            ->addOption('show-env', null, InputOption::VALUE_NONE, 'Prefix the output with APP_KEY=');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $length = filter_var($input->getOption('length'), FILTER_VALIDATE_INT);

        if (!is_int($length) || $length <= 0) {
            $output->writeln('<error>The --length option must be a positive integer.</error>');

            return Command::INVALID;
        }

        $key = generate_key($length, !(bool) $input->getOption('raw'));
        $value = (bool) $input->getOption('show-env') ? 'APP_KEY=' . $key : $key;

        $output->writeln($value);

        return Command::SUCCESS;
    }
}
