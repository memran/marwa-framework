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

#[AsCommand(name: 'mcp:serve', description: 'Start MCP server')]
final class MCPServeCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('transport', InputArgument::OPTIONAL, 'Transport type (stdio or http)', 'stdio')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'HTTP port', 8080);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transport = $input->getArgument('transport');

        if (!in_array($transport, ['stdio', 'http'], true)) {
            $output->writeln('<error>Invalid transport. Use: stdio or http</error>');
            return Command::INVALID;
        }

        $output->writeln(sprintf('<info>Starting MCP server on %s transport...</info>', $transport));

        if ($transport === 'http') {
            $port = (int) $input->getOption('port');
            $output->writeln(sprintf('<info>Listening on port: %d</info>', $port));
        }

        if (!$this->app()->has(\Marwa\Framework\Contracts\MCP\MCPServerInterface::class)) {
            $output->writeln('<error>MCP support is not installed. Require memran/marwa-mcp to use this command.</error>');

            return Command::FAILURE;
        }

        $mcp = $this->app()->make(\Marwa\Framework\Contracts\MCP\MCPServerInterface::class);

        try {
            $mcp->serve($transport);
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>Server error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
