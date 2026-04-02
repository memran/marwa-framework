<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Scheduling\Scheduler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'schedule:run', description: 'Run due scheduled tasks. Use --for=60 to let cron drive per-second execution.')]
final class ScheduleRunCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('for', null, InputOption::VALUE_REQUIRED, 'Number of seconds to keep the scheduler loop alive.', null)
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds to sleep between loop iterations.', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Scheduler $scheduler */
        $scheduler = $this->app()->make(Scheduler::class);
        $config = $scheduler->configuration();
        $loopSeconds = $this->resolveIntegerOption($input->getOption('for'), $config['defaultLoopSeconds']);
        $sleepSeconds = $this->resolveIntegerOption($input->getOption('sleep'), $config['defaultSleepSeconds']);

        if ($loopSeconds <= 0 || $sleepSeconds <= 0) {
            $output->writeln('<error>The --for and --sleep options must be positive integers.</error>');

            return Command::INVALID;
        }

        $startedAt = time();
        $hadDueTask = false;

        do {
            $summary = $scheduler->runDue(new \DateTimeImmutable());

            foreach ($summary['ran'] as $task) {
                $hadDueTask = true;
                $output->writeln(sprintf('<info>Ran [%s]</info>', $task));
            }

            foreach ($summary['skipped'] as $task) {
                $hadDueTask = true;
                $output->writeln(sprintf('<comment>Skipped [%s] because it is already running.</comment>', $task));
            }

            foreach ($summary['failed'] as $task) {
                $hadDueTask = true;
                $output->writeln(sprintf('<error>Task [%s] failed.</error>', $task));
            }

            if ((time() - $startedAt + 1) >= $loopSeconds) {
                break;
            }

            sleep($sleepSeconds);
        } while (true);

        if (!$hadDueTask) {
            $output->writeln('<comment>No scheduled tasks were due.</comment>');
        }

        return Command::SUCCESS;
    }

    private function resolveIntegerOption(mixed $value, int $default): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $resolved = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($resolved) ? $resolved : 0;
    }
}
