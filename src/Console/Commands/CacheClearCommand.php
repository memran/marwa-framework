<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\CacheConfig;
use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Views\View;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cache:clear', description: 'Clear all application caches including bootstrap, view, and framework caches.')]
final class CacheClearCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->clearBootstrapCaches($output);
        $this->clearViewCache($output);
        $this->clearFrameworkCache($output);

        $output->writeln('');
        $output->writeln('<info>Cache cleared successfully!</info>');

        return Command::SUCCESS;
    }

    private function clearBootstrapCaches(OutputInterface $output): void
    {
        foreach ([
            'config' => ConfigClearCommand::class,
            'route' => RouteClearCommand::class,
            'module' => ModuleClearCommand::class,
        ] as $name => $commandClass) {
            $command = $this->container()->get($commandClass);

            if ($command instanceof \Marwa\Framework\Console\AbstractCommand) {
                $command->setMarwaApplication($this->app());
            }

            $output->write("Clearing {$name} cache... ");
            $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), $output);
            $output->writeln('<info>done</info>');
        }
    }

    private function clearViewCache(OutputInterface $output): void
    {
        $output->write('Clearing view cache... ');

        try {
            /** @var View $view */
            $view = $this->container()->get(View::class);
            $view->clearCache();
            $output->writeln('<info>done</info>');
        } catch (\Throwable $e) {
            $output->writeln('<comment>skipped (not available)</comment>');
        }
    }

    private function clearFrameworkCache(OutputInterface $output): void
    {
        $output->write('Clearing framework cache... ');

        $this->config()->loadIfExists(CacheConfig::KEY . '.php');
        $config = array_replace_recursive(CacheConfig::defaults($this->app()), $this->config()->getArray(CacheConfig::KEY, []));
        $driver = (string) ($config['driver'] ?? 'file');

        if ($driver === 'sqlite') {
            $path = $config['sqlite']['path'] ?? null;

            if ($path !== null && is_file($path)) {
                unlink($path);
            }
        } else {
            $path = (string) ($config['file']['path'] ?? '');

            if ($path !== '' && is_dir($path)) {
                $this->deleteDirectoryContents($path);
            }
        }

        $output->writeln('<info>done</info>');
    }

    private function deleteDirectoryContents(string $directory): void
    {
        $entries = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*');

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if (is_dir($entry)) {
                $this->deleteDirectoryContents($entry);
                @rmdir($entry);

                continue;
            }

            @unlink($entry);
        }

        @rmdir($directory);
    }
}
