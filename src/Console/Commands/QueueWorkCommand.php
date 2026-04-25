<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Application;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Queue\MailJob;
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
            ->addOption('stop-when-empty', null, InputOption::VALUE_NONE, 'Exit after all available jobs have been processed.')
            ->addOption('max-jobs', null, InputOption::VALUE_REQUIRED, 'Maximum number of jobs to process before exiting.')
            ->addOption('max-time', null, InputOption::VALUE_REQUIRED, 'Maximum number of seconds to run before exiting.')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Deprecated alias for --max-time.')
            ->addOption('tries', null, InputOption::VALUE_REQUIRED, 'Number of times to attempt a job before failing it.')
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds to sleep when no jobs are available.', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueNames = $this->parseQueues((string) $input->getOption('queue'));
        $once = (bool) $input->getOption('once');
        $stopWhenEmpty = (bool) $input->getOption('stop-when-empty');
        $maxJobs = $this->positiveIntOption($input->getOption('max-jobs'));
        $maxTime = $this->positiveIntOption($input->getOption('max-time'));
        $timeout = $this->positiveIntOption($input->getOption('timeout'));
        $sleep = max(0, (int) $input->getOption('sleep'));

        if ($maxTime === null && $timeout !== null) {
            $maxTime = $timeout;
        }

        $app = $this->app();
        $config = $this->loadQueueConfig($app);
        $tries = $this->resolveTries($this->positiveIntOption($input->getOption('tries')), $config['tries'], $config['retryAfter']);
        $retryDelay = $config['retryAfter'];

        if (!$config['enabled']) {
            $output->writeln('<comment>Queue support is disabled.</comment>');
            $output->writeln('<info>Processed 0 jobs</info>');
            return Command::SUCCESS;
        }

        /** @var QueueInterface $queue */
        $queue = $app->make(QueueInterface::class);

        $output->writeln('<info>Processing jobs from queues: ' . implode(', ', $queueNames) . '</info>');

        $processed = 0;
        $startTime = time();

        while (true) {
            if ($maxJobs !== null && $processed >= $maxJobs) {
                break;
            }

            if ($maxTime !== null && (time() - $startTime) >= $maxTime) {
                break;
            }

            try {
                $job = $this->popNext($queue, $queueNames);

                if ($job === null) {
                    if ($once || $stopWhenEmpty) {
                        break;
                    }

                    $this->sleepWhenIdle($sleep);
                    continue;
                }

                $output->writeln("<comment>Processing job: {$job->name()} ({$job->id()})</comment>");

                $this->processJob($app, $job, $tries, $retryDelay, $output, $queue);

                $processed++;

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
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     default: string,
     *     path: string,
     *     database: array{connection: string, table: string},
     *     retryAfter: int,
     *     tries: int|null
     * }
     */
    private function loadQueueConfig(Application $app): array
    {
        $config = $app->make(\Marwa\Framework\Supports\Config::class);
        $config->loadIfExists(QueueConfig::KEY . '.php');

        return QueueConfig::merge($app, $config->getArray(QueueConfig::KEY, []));
    }

    private function resolveTries(?int $requestedTries, ?int $configuredTries, int $retryAfter): int
    {
        if ($requestedTries !== null && $requestedTries > 0) {
            return $requestedTries;
        }

        if ($configuredTries !== null && $configuredTries > 0) {
            return $configuredTries;
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
        try {
            $result = $this->handleJob($app, $job);
        } catch (\Throwable $e) {
            $attempts = max(1, $job->attempts());
            $output->writeln("<error>Job failed (attempt {$attempts}/{$maxTries}): {$e->getMessage()}</error>");

            if ($attempts >= $maxTries) {
                $queue->fail($job, $e->getMessage());
                $output->writeln('<error>Job failed permanently</error>');
                return;
            }

            $delaySeconds = max(1, $retryDelay);
            $queue->release($job, $delaySeconds);
            $output->writeln("<info>Job released with {$delaySeconds}s delay</info>");
            return;
        }

        $queue->complete($job);
        $output->writeln('<info>Job returned: ' . $this->formatResult($result) . '</info>');
        $output->writeln('<info>Job completed successfully</info>');
    }

    /**
     * @param list<string> $queues
     */
    private function popNext(QueueInterface $queue, array $queues): ?QueuedJob
    {
        foreach ($queues as $queueName) {
            $job = $queue->pop($queueName, null);

            if ($job !== null) {
                return $job;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function parseQueues(string $queue): array
    {
        $queues = array_values(array_filter(array_map(
            static fn (string $name): string => trim($name),
            explode(',', $queue)
        ), static fn (string $name): bool => $name !== ''));

        return $queues !== [] ? $queues : ['default'];
    }

    private function positiveIntOption(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    private function sleepWhenIdle(int $sleep): void
    {
        if ($sleep > 0) {
            sleep($sleep);
            return;
        }

        usleep(100000);
    }

    private function handleJob(Application $app, QueuedJob $job): mixed
    {
        if ($job->name() === MailJob::NAME) {
            return MailJob::fromArray($job->payload())->handle($app);
        }

        $jobClass = $job->name();

        if (!class_exists($jobClass)) {
            throw new \RuntimeException("Job class {$jobClass} does not exist");
        }

        $jobInstance = new $jobClass($job->payload());

        if (!method_exists($jobInstance, 'handle')) {
            throw new \RuntimeException("Job class {$jobClass} must define a handle method");
        }

        return $jobInstance->handle($app);
    }

    private function formatResult(mixed $result): string
    {
        if ($result === null) {
            return 'null';
        }

        if (is_bool($result)) {
            return $result ? 'true' : 'false';
        }

        if (is_scalar($result)) {
            return (string) $result;
        }

        $encoded = json_encode($result, JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : get_debug_type($result);
    }
}
