<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\ConsoleConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:ai-helper', description: 'Generate AI helper stubs for application-specific assistants.')]
final class MakeAiHelperCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Helper class name, for example SupportAgent.')
            ->addOption('with-command', null, InputOption::VALUE_NONE, 'Also generate a matching console command stub.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));

        if ($name === '') {
            $output->writeln('<error>A helper name is required.</error>');

            return Command::INVALID;
        }

        $className = preg_replace('/[^A-Za-z0-9_]/', '', $name) ?: 'AiHelper';
        $stubsPath = $this->config()->getString(ConsoleConfig::KEY . '.stubsPath', ConsoleConfig::defaults($this->app())['stubsPath']);
        $helperTarget = $this->basePath('app/AI/' . $className . '.php');

        $this->writeStub(
            $stubsPath . '/ai-helper.stub',
            $helperTarget,
            [
                '{{ namespace }}' => 'App\\AI',
                '{{ class }}' => $className,
            ]
        );

        $output->writeln(sprintf('<info>Created AI helper:</info> %s', $helperTarget));

        if ((bool) $input->getOption('with-command')) {
            $commandClass = $className . 'Command';
            $commandName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className) ?: 'ai-helper');
            $commandTarget = $this->basePath('app/Console/Commands/' . $commandClass . '.php');

            $this->writeStub(
                $stubsPath . '/ai-command.stub',
                $commandTarget,
                [
                    '{{ namespace }}' => 'App\\Console\\Commands',
                    '{{ class }}' => $commandClass,
                    '{{ helper_class }}' => $className,
                    '{{ command_name }}' => 'ai:' . $commandName,
                ]
            );

            $output->writeln(sprintf('<info>Created AI command:</info> %s', $commandTarget));
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, string> $replacements
     */
    private function writeStub(string $stubPath, string $targetPath, array $replacements): void
    {
        if (!is_file($stubPath)) {
            throw new \RuntimeException(sprintf('Stub file [%s] was not found.', $stubPath));
        }

        $directory = dirname($targetPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $directory));
        }

        $contents = (string) file_get_contents($stubPath);
        $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);

        file_put_contents($targetPath, $contents);
    }
}
