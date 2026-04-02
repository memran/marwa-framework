<?php

declare(strict_types=1);

namespace Marwa\Framework\Console;

use Marwa\Framework\Application;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Command\Command;

final class CommandDiscovery
{
    public function __construct(
        private Application $app,
        private LoggerInterface $logger
    ) {}

    /**
     * @param list<array{namespace?:string,path?:string,optional?:bool}> $sources
     * @return list<class-string>
     */
    public function discover(array $sources): array
    {
        $commands = [];

        foreach ($sources as $source) {
            $commands = [...$commands, ...$this->discoverFromSource($source)];
        }

        return array_values(array_unique($commands));
    }

    /**
     * @param array{namespace?:string,path?:string,optional?:bool} $source
     * @return list<class-string>
     */
    private function discoverFromSource(array $source): array
    {
        $namespace = is_string($source['namespace'] ?? null) ? trim($source['namespace'], '\\') : '';
        $optional = (bool)($source['optional'] ?? false);

        $path = $source['path'] ?? $this->resolveNamespacePath($namespace);

        if (!is_string($path) || $path === '') {
            if (!$optional) {
                $this->logger->warning('Skipping console discovery source without a resolvable path.', [
                    'namespace' => $namespace !== '' ? $namespace : null,
                ]);
            }

            return [];
        }

        $resolvedPath = $this->resolvePath($path);
        $namespace = $namespace !== '' ? $namespace : $this->inferNamespaceFromPath($resolvedPath);

        if ($namespace === null || $namespace === '') {
            if (!$optional) {
                $this->logger->warning('Skipping console discovery source without a resolvable namespace.', [
                    'path' => $resolvedPath,
                ]);
            }

            return [];
        }

        if (!is_dir($resolvedPath)) {
            if (!$optional) {
                $this->logger->warning('Skipping missing console discovery directory.', [
                    'namespace' => $namespace,
                    'path' => $resolvedPath,
                ]);
            }

            return [];
        }

        return $this->discoverClasses($namespace, $resolvedPath);
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return $path;
        }

        return $this->app->basePath($path);
    }

    private function resolveNamespacePath(string $namespace): ?string
    {
        $map = $this->autoloadPsr4Map();
        $namespaceWithSeparator = $namespace . '\\';
        $candidates = array_keys($map);

        usort($candidates, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        foreach ($candidates as $prefix) {
            $trimmedPrefix = trim($prefix, '\\');

            if ($namespace !== $trimmedPrefix && !str_starts_with($namespaceWithSeparator, $prefix)) {
                continue;
            }

            $basePaths = $map[$prefix];
            $basePath = $basePaths[0] ?? null;

            if (!is_string($basePath) || $basePath === '') {
                return null;
            }

            $relativeNamespace = $namespace === $trimmedPrefix
                ? ''
                : substr($namespaceWithSeparator, strlen($prefix));

            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, trim($relativeNamespace, '\\'));

            return $relativePath === '' ? $basePath : $basePath . DIRECTORY_SEPARATOR . $relativePath;
        }

        return null;
    }

    private function inferNamespaceFromPath(string $path): ?string
    {
        $resolvedPath = $this->normalizePath(realpath($path) ?: $path);
        $map = $this->autoloadPsr4Map();
        $matches = [];

        foreach ($map as $prefix => $basePaths) {
            foreach ($basePaths as $basePath) {
                $normalizedBasePath = $this->normalizePath(realpath($basePath) ?: $basePath);

                if (!str_starts_with($resolvedPath . DIRECTORY_SEPARATOR, rtrim($normalizedBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
                    && $resolvedPath !== $normalizedBasePath) {
                    continue;
                }

                $matches[] = [
                    'prefix' => trim($prefix, '\\'),
                    'basePath' => $normalizedBasePath,
                ];
            }
        }

        if ($matches === []) {
            return null;
        }

        usort(
            $matches,
            static fn (array $left, array $right): int => strlen($right['basePath']) <=> strlen($left['basePath'])
        );

        $match = $matches[0];
        $relativePath = trim(substr($resolvedPath, strlen($match['basePath'])), DIRECTORY_SEPARATOR);
        $relativeNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        return $relativeNamespace === ''
            ? $match['prefix']
            : $match['prefix'] . '\\' . $relativeNamespace;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function autoloadPsr4Map(): array
    {
        foreach (spl_autoload_functions() ?: [] as $autoloadFunction) {
            if (!is_array($autoloadFunction) || !isset($autoloadFunction[0]) || !$autoloadFunction[0] instanceof \Composer\Autoload\ClassLoader) {
                continue;
            }

            /** @var array<string, array<int, string>> $map */
            $map = $autoloadFunction[0]->getPrefixesPsr4();

            return $map;
        }

        foreach ([
            $this->app->basePath('vendor/composer/autoload_psr4.php'),
            dirname(__DIR__, 2) . '/vendor/composer/autoload_psr4.php',
        ] as $autoloadFile) {
            if (!is_file($autoloadFile)) {
                continue;
            }

            /** @var array<string, array<int, string>> $map */
            $map = require $autoloadFile;

            return $map;
        }

        return [];
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    /**
     * @return list<class-string>
     */
    private function discoverClasses(string $namespace, string $path): array
    {
        $commands = [];
        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)),
            '/^.+Command\.php$/i'
        );

        foreach ($iterator as $file) {
            $pathname = $file->getPathname();
            $relative = substr($pathname, strlen(rtrim($path, DIRECTORY_SEPARATOR)) + 1);
            $class = $namespace . '\\' . str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relative
            );

            if (!class_exists($class)) {
                continue;
            }

            if (!is_subclass_of($class, Command::class)) {
                $this->logger->warning('Skipping discovered class that is not a Symfony command.', [
                    'command' => $class,
                ]);
                continue;
            }

            $commands[] = $class;
        }

        return $commands;
    }
}
