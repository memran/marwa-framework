<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Security\RiskAnalyzer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'security:report', description: 'Summarize recorded security risk signals and optionally prune old entries.')]
final class SecurityReportCommand extends AbstractCommand
{
    public function __construct(
        private RiskAnalyzer $riskAnalyzer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('since-hours', null, InputOption::VALUE_OPTIONAL, 'Only include signals newer than the given number of hours.', null)
            ->addOption('prune-days', null, InputOption::VALUE_OPTIONAL, 'Remove signals older than the given number of days after reporting.', null)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Render the report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sinceHours = $this->optionInt($input->getOption('since-hours'));
        $pruneDays = $this->optionInt($input->getOption('prune-days'));
        $report = $this->riskAnalyzer->report($sinceHours);

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $output->writeln(sprintf('<info>Total signals:</info> %d', $report['total']));
            $output->writeln('<info>By category:</info>');

            foreach ($report['byCategory'] as $category => $count) {
                $output->writeln(sprintf('  - %s: %d', $category, $count));
            }

            $output->writeln('<info>By score:</info>');
            foreach ($report['byScore'] as $bucket => $count) {
                $output->writeln(sprintf('  - %s: %d', $bucket, $count));
            }

            if ($report['latest'] !== []) {
                $output->writeln('<info>Latest signals:</info>');
                foreach ($report['latest'] as $entry) {
                    $output->writeln(sprintf(
                        '  - [%s] %s (%s)',
                        (string) ($entry['timestamp'] ?? ''),
                        (string) ($entry['message'] ?? ''),
                        (string) ($entry['category'] ?? 'unknown')
                    ));
                }
            }
        }

        if ($pruneDays !== null && $pruneDays > 0) {
            $removed = $this->riskAnalyzer->prune($pruneDays);
            $output->writeln(sprintf('<comment>Pruned %d old signals.</comment>', $removed));
        }

        return Command::SUCCESS;
    }

    private function optionInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($int) ? $int : null;
    }
}
