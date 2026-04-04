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

#[AsCommand(name: 'make:mail', description: 'Generate a mail class for the application.')]
final class MakeMailCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Mail class name, for example WelcomeMail or Billing/InvoiceMail.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite an existing mail file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));

        if ($name === '') {
            $output->writeln('<error>A mail class name is required.</error>');

            return Command::INVALID;
        }

        $target = $this->buildClassTarget($name, 'App\\Mail', 'app/Mail', 'GeneratedMail', 'Mail');

        $this->writeStubFile(
            $this->frameworkStubPath('console/mail.stub'),
            $target['target'],
            [
                '{{ namespace }}' => $target['namespace'],
                '{{ class }}' => $target['class'],
            ],
            (bool) $input->getOption('force')
        );

        $output->writeln(sprintf('<info>Created mail class:</info> %s', $target['target']));

        return Command::SUCCESS;
    }
}
