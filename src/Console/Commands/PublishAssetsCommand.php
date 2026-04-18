<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'module:publish', description: 'Publish module public assets')]
final class PublishAssetsCommand extends AbstractCommand
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = $input->getArgument('module');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $output->writeln('<info>Dry run mode - no files will be copied</info>');
        }

        $published = 0;

        if ($moduleName) {
            $published = $this->publishModule($moduleName, $output, (bool) $dryRun);
        } else {
            $published = $this->publishAll($output, (bool) $dryRun);
        }

        $output->writeln("<info>Published {$published} asset file(s)</info>");

        return self::SUCCESS;
    }

    private function publishModule(string $name, OutputInterface $output, bool $dryRun): int
    {
        $modules = $this->app()->modules();
        $module = $modules[$name] ?? null;

        if ($module === null) {
            $output->writeln("<error>Module [{$name}] not found</error>");

            return self::FAILURE;
        }

        return $this->publishFromModule($module, $output, $dryRun);
    }

    private function publishAll(OutputInterface $output, bool $dryRun): int
    {
        $published = 0;

        foreach ($this->app()->modules() as $module) {
            $count = $this->publishFromModule($module, $output, $dryRun);
            $published += $count;
        }

        return $published;
    }

    /**
     * @param \Marwa\Module\Module $module
     */
    private function publishFromModule($module, OutputInterface $output, bool $dryRun): int
    {
        $slug = $module->slug();
        $basePath = $module->basePath();
        $publicPath = $basePath . DIRECTORY_SEPARATOR . 'public';

        if (!is_dir($publicPath)) {
            return 0;
        }

        $targetDir = public_path('assets' . DIRECTORY_SEPARATOR . $slug);

        if (!$dryRun) {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        $finder = new Finder();
        $finder->in($publicPath)->files();

        $count = 0;
        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $relativePath;
            $targetDirForFile = dirname($targetPath);

            if (!$dryRun && !is_dir($targetDirForFile)) {
                mkdir($targetDirForFile, 0755, true);
            }

            if ($dryRun) {
                $output->writeln("  <comment>Would copy:</comment> {$slug}/{$relativePath}");
            } else {
                copy($file->getPathname(), $targetPath);
                $output->writeln("  <info>Copied:</info> {$slug}/{$relativePath}");
            }
            $count++;
        }

        return $count;
    }

    protected function configure(): void
    {
        $this->addArgument('module', null, 'Module name to publish assets for (optional)');
        $this->addOption('dry-run', null, null, 'Preview files without copying');
    }
}
