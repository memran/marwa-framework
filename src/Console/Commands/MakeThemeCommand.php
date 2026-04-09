<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\ViewConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:theme', description: 'Generate a view theme folder compatible with marwa-view theme bootstrapping.')]
final class MakeThemeCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Theme name, for example default or dark.')
            ->addOption('parent', null, InputOption::VALUE_REQUIRED, 'Optional parent theme name for inheritance.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing manifest and starter files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));

        if ($name === '') {
            $output->writeln('<error>A theme name is required.</error>');

            return Command::INVALID;
        }

        $themeName = $this->normalizeThemeName($name);
        $themePath = $this->themesBasePath() . DIRECTORY_SEPARATOR . $themeName;
        $parentTheme = trim((string) $input->getOption('parent'));
        $parentTheme = $parentTheme !== '' ? $this->normalizeThemeName($parentTheme) : '';

        $this->ensureDirectory($themePath);
        $this->ensureDirectory($themePath . '/views/home');
        $this->ensureDirectory($themePath . '/assets/css');
        $this->ensureDirectory($themePath . '/assets/images');

        $this->writeStubFile(
            $this->frameworkStubPath('console/theme-manifest.stub'),
            $themePath . '/manifest.php',
            [
                '{{ theme_name }}' => $themeName,
                '{{ parent_line }}' => $parentTheme !== '' ? "    'parent' => '{$parentTheme}',\n" : '',
                '{{ assets_url }}' => '/themes/' . $themeName,
            ],
            (bool) $input->getOption('force')
        );

        $this->writeStubFile(
            $this->frameworkStubPath('console/theme-layout.stub'),
            $themePath . '/views/layout.twig',
            [
                '{{ theme_name }}' => $themeName,
            ],
            (bool) $input->getOption('force')
        );

        $this->writeStubFile(
            $this->frameworkStubPath('console/theme-home.stub'),
            $themePath . '/views/home/index.twig',
            [
                '{{ theme_name }}' => $themeName,
            ],
            (bool) $input->getOption('force')
        );

        $this->writeStubFile(
            $this->frameworkStubPath('console/theme-css.stub'),
            $themePath . '/assets/css/app.css',
            [
                '{{ theme_name }}' => $themeName,
            ],
            (bool) $input->getOption('force')
        );

        $output->writeln(sprintf('<info>Created theme:</info> %s', $themePath));

        return Command::SUCCESS;
    }

    private function themesBasePath(): string
    {
        $config = array_replace_recursive(ViewConfig::defaults($this->app()), $this->config()->getArray(ViewConfig::KEY, []));
        $viewsPath = $config['themePath'] ?? $this->app()->basePath('resources/views/themes');

        if (!is_string($viewsPath) || $viewsPath === '') {
            throw new \RuntimeException('The configured theme path is invalid.');
        }

        return rtrim($viewsPath, DIRECTORY_SEPARATOR);
    }

    private function normalizeThemeName(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_-]/', '', trim($value)) ?: '';

        if ($normalized === '') {
            throw new \RuntimeException('Theme name must contain at least one letter or number.');
        }

        return $normalized;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $path));
        }
    }
}
