<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Application;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Queue\QueuedJob;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'queue:work', description: 'Process jobs from the queue.')]
final class QueueWorkCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'The queue to work on.', 'default')
            ->addOption('once', null, InputOption::VALUE_NONE, 'Process only one job and exit.')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Seconds to wait for a job.', 60)
            ->addOption('tries', null, InputOption::VALUE_REQUIRED, 'Number of times to attempt a job.', 3)
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds to sleep when no jobs are available.', 3);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = (string) $input->getOption('queue');
        $once = (bool) $input->getOption('once');
        $timeout = (int) $input->getOption('timeout');
        $sleep = (int) $input->getOption('sleep');

        $app = $this->app();
        /** @var QueueInterface $queue */
        $queue = $app->make(QueueInterface::class);

        $config = $this->loadQueueConfig($app);
        $tries = $this->resolveTries($config['tries'] ?? null, $config['retryAfter']);
        $retryDelay = $config['retryAfter'];

        $output->writeln("<info>Processing jobs from queue: {$queueName}</info>");

        $processed = 0;
        $startTime = time();

        while (true) {
            try {
                $job = $queue->pop($queueName, null);

                if ($job === null) {
                    if ($once || (time() - $startTime) >= $timeout) {
                        break;
                    }
                    sleep($sleep);
                    continue;
                }

                $output->writeln("<comment>Processing job: {$job->name()} ({$job->id()})</comment>");

                $this->processJob($app, $job, $tries, $retryDelay, $output, $queue);

                $processed++;
                $output->writeln("<info>Job completed successfully</info>");

                if ($once) {
                    break;
                }

            } catch (\Throwable $e) {
                $output->writeln("<error>Error processing queue: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }
        }

        $output->writeln("<info>Processed {$processed} jobs</info>");
        return Command::SUCCESS;
    }

    /**
     * @return array{retryAfter: int, tries: int|null}
     */
    private function loadQueueConfig(Application $app): array
    {
        $config = $app->make(\Marwa\Framework\Supports\Config::class);
        $config->loadIfExists(QueueConfig::KEY . '.php');

        return QueueConfig::merge($app, $config->getArray(QueueConfig::KEY, []));
    }

    private function resolveTries(?int $tries, int $retryAfter): int
    {
        if ($tries !== null && $tries > 0) {
            return $tries;
        }

        return (int) max(1, $retryAfter / 30);
    }

    private function processJob(
        Application $app,
        QueuedJob $job,
        int $maxTries,
        int $retryDelay,
        OutputInterface $output,
        QueueInterface $queue
    ): void {
        $attempts = 0;

        while ($attempts < $maxTries) {
            try {
                $jobClass = $job->name();

                if (!class_exists($jobClass)) {
                    throw new \RuntimeException("Job class {$jobClass} does not exist");
                }

                $jobInstance = new $jobClass($job->payload());
                $result = $jobInstance->handle($app);

                $output->writeln("<info>Job returned: {$result}</info>");
                return;

            } catch (\Throwable $e) {
                $attempts++;
                $output->writeln("<error>Job failed (attempt {$attempts}/{$maxTries}): {$e->getMessage()}</error>");

                if ($attempts >= $maxTries) {
                    $delaySeconds = max(3, $retryDelay);
                    $queue->release($job, $delaySeconds);
                    $output->writeln("<info>Job released with {$delaySeconds}s delay</info>");

                    return;
                }

                sleep(1);
            }
        }
    }
}
