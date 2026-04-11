<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\ModuleConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:module', description: 'Generate a module folder structure compatible with marwa-module.')]
final class MakeModuleCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Module name, for example Blog.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite an existing manifest and provider files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));

        if ($name === '') {
            $output->writeln('<error>A module name is required.</error>');

            return Command::INVALID;
        }

        $moduleName = $this->normalizeModuleName($name);
        $moduleSlug = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $moduleName));
        $modulesRoot = $this->moduleRootPath();
        $modulePath = rtrim($modulesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $moduleName;
        $providerClass = $moduleName . 'ServiceProvider';
        $providerNamespace = 'App\\Modules\\' . $moduleName;

        $this->ensureDirectory($modulePath);
        $this->ensureDirectory($modulePath . '/routes');
        $this->ensureDirectory($modulePath . '/resources/views');
        $this->ensureDirectory($modulePath . '/Console/Commands');
        $this->ensureDirectory($modulePath . '/database/migrations');

        $this->writeStubFile(
            $this->frameworkStubPath('console/module-manifest.stub'),
            $modulePath . '/manifest.php',
            [
                '{{ module_name }}' => $moduleName . ' Module',
                '{{ module_slug }}' => $moduleSlug,
                '{{ provider_class }}' => $providerNamespace . '\\' . $providerClass,
            ],
            (bool) $input->getOption('force')
        );

        $this->writeStubFile(
            $this->frameworkStubPath('console/module-provider.stub'),
            $modulePath . '/' . $providerClass . '.php',
            [
                '{{ namespace }}' => $providerNamespace,
                '{{ class }}' => $providerClass,
                '{{ slug }}' => $moduleSlug,
            ],
            (bool) $input->getOption('force')
        );

        $this->writeStubFile(
            $this->frameworkStubPath('console/module-route.stub'),
            $modulePath . '/routes/http.php',
            [
                '{{ module_slug }}' => $moduleSlug,
                '{{ module_name }}' => $moduleName,
            ],
            (bool) $input->getOption('force')
        );

        $this->writeStubFile(
            $this->frameworkStubPath('console/module-view.stub'),
            $modulePath . '/resources/views/index.twig',
            [
                '{{ module_name }}' => $moduleName,
            ],
            (bool) $input->getOption('force')
        );

        $output->writeln(sprintf('<info>Created module:</info> %s', $modulePath));
        $output->writeln('<comment>Remember to map App\\Modules\\ to modules/ in your host application composer.json.</comment>');

        return Command::SUCCESS;
    }

    private function moduleRootPath(): string
    {
        $config = array_replace_recursive(ModuleConfig::defaults($this->app()), $this->config()->getArray(ModuleConfig::KEY, []));
        $paths = array_values(array_filter($config['paths'] ?? [], static fn (mixed $path): bool => is_string($path) && $path !== ''));

        if ($paths === []) {
            throw new \RuntimeException('No module paths are configured. Define module.paths in config/module.php.');
        }

        return $paths[0];
    }

    private function normalizeModuleName(string $name): string
    {
        $segments = preg_split('/[\\\\\/]+/', $name) ?: [];
        $segments = array_values(array_filter(array_map(
            static fn (string $segment): string => preg_replace('/[^A-Za-z0-9_]/', '', $segment) ?: '',
            $segments
        )));

        return ucfirst($segments === [] ? 'Module' : end($segments));
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $path));
        }
    }
}
