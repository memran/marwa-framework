<?php

declare(strict_types=1);

namespace Marwa\Framework\Console;

use League\Container\Container;
use Marwa\Framework\Application;
use Marwa\Framework\Supports\Config;
use Marwa\Support\File;
use Marwa\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    private ?Application $marwaApp = null;

    public function setMarwaApplication(Application $app): static
    {
        $this->marwaApp = $app;

        return $this;
    }

    protected function app(): Application
    {
        if (!$this->marwaApp instanceof Application) {
            throw new \LogicException(sprintf('Command [%s] is not attached to a Marwa application.', static::class));
        }

        return $this->marwaApp;
    }

    protected function container(): Container
    {
        return $this->app()->container();
    }

    protected function config(): Config
    {
        return $this->container()->get(Config::class);
    }

    protected function logger(): LoggerInterface
    {
        return $this->container()->get(LoggerInterface::class);
    }

    protected function basePath(string $path = ''): string
    {
        return $this->app()->basePath($path);
    }

    /**
     * @return array{namespace:string,class:string,target:string}
     */
    protected function buildClassTarget(
        string $name,
        string $baseNamespace,
        string $baseDirectory,
        string $defaultClass,
        string $suffix = ''
    ): array {
        $segments = preg_split('/[\\\\\/]+/', trim($name)) ?: [];
        $segments = array_values(array_filter(array_map(
            static fn (string $segment): string => preg_replace('/[^A-Za-z0-9_]/', '', $segment) ?: '',
            $segments
        )));

        if ($segments === []) {
            $segments = [$defaultClass];
        }

        $className = array_pop($segments) ?: $defaultClass;

        if ($suffix !== '' && !Str::endsWith($className, $suffix)) {
            $className .= $suffix;
        }

        $namespace = $baseNamespace . ($segments !== [] ? '\\' . implode('\\', $segments) : '');
        $relativeDirectory = $baseDirectory . ($segments !== [] ? '/' . implode('/', $segments) : '');

        return [
            'namespace' => $namespace,
            'class' => $className,
            'target' => $this->basePath($relativeDirectory . '/' . $className . '.php'),
        ];
    }

    protected function frameworkStubPath(string $relativePath): string
    {
        return dirname(__DIR__) . '/Stubs/' . ltrim($relativePath, '/');
    }

    /**
     * @param array<string, string> $replacements
     */
    protected function writeStubFile(string $stubPath, string $targetPath, array $replacements, bool $force = false): void
    {
        if (!File::exists($stubPath)) {
            throw new \RuntimeException(sprintf('Stub file [%s] was not found.', $stubPath));
        }

        if (!$force && File::exists($targetPath)) {
            throw new \RuntimeException(sprintf('Target file [%s] already exists.', $targetPath));
        }

        $directory = dirname($targetPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $directory));
        }

        $contents = File::get($stubPath);
        $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);

        File::put($targetPath, $contents, 0, 0644, false);
    }
}
