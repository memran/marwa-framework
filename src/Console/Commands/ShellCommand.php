<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Contracts\ShellFactoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'shell', description: 'Open an interactive PsySH shell when the optional package is installed.')]
final class ShellCommand extends AbstractCommand
{
    public function __construct(
        private ShellFactoryInterface $shellFactory
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->shellFactory->available()) {
            $output->writeln('<comment>PsySH is not installed.</comment>');
            $output->writeln('<comment>Install it with: composer require --dev psy/psysh</comment>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Starting PsySH shell. Available variables: $app, $container, $config, $logger</info>');

        return $this->shellFactory->run($this->app(), [
            'app' => $this->app(),
            'container' => $this->container(),
            'config' => $this->config(),
            'logger' => $this->logger(),
        ]);
    }
}
