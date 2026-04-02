<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Modules\Blog\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'blog:hello', description: 'Example module command.')]
final class BlogHelloCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello from blog module');

        return Command::SUCCESS;
    }
}
