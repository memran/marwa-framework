<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:run', description: 'Demo command for console discovery tests.')]
final class DemoCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->basePath());

        return Command::SUCCESS;
    }
}
