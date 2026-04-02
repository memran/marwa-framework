<?php

declare(strict_types=1);

namespace Marwa\Framework\Console;

use League\Container\Container;
use Marwa\Framework\Application;
use Marwa\Framework\Supports\Config;
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
}
