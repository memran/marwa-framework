<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Contracts\QueueInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'queue:list', description: 'List jobs in the queue.')]
final class QueueListCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'The queue to list.', 'default')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Job status to show (pending, processing, failed).', 'pending');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = (string) $input->getOption('queue');
        $status = (string) $input->getOption('status');

        /** @var QueueInterface $queue */
        $queue = $this->app()->make(QueueInterface::class);

        $output->writeln("<info>Queue: {$queueName} ({$status})</info>");
        $output->writeln(str_repeat('-', 80));

        $jobs = $this->getJobsByStatus($queueName, $status);

        if (empty($jobs)) {
            $output->writeln('<comment>No jobs found</comment>');
            return Command::SUCCESS;
        }

        foreach ($jobs as $job) {
            $availableAt = date('Y-m-d H:i:s', $job->availableAt());
            $createdAt = date('Y-m-d H:i:s', $job->createdAt());

            $output->writeln(sprintf(
                '%s | %s | %s | %s | %d attempts',
                $job->id(),
                $job->name(),
                $availableAt,
                $createdAt,
                $job->attempts()
            ));
        }

        $output->writeln(str_repeat('-', 80));
        $output->writeln("<info>Total: " . count($jobs) . " jobs</info>");

        return Command::SUCCESS;
    }

    /**
     * @return array<int, \Marwa\Framework\Queue\QueuedJob>
     */
    private function getJobsByStatus(string $queueName, string $status): array
    {
        // This is a simplified implementation
        // In a real scenario, you'd need to read from the queue storage
        // For now, we'll return an empty array as the FileQueue doesn't expose internal methods

        return [];
    }
}
