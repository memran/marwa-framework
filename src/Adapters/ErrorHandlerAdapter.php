<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use Marwa\ErrorHandler\Contracts\RendererInterface;
use Marwa\ErrorHandler\ErrorHandler;
use Marwa\ErrorHandler\Support\FallbackRenderer;
use Marwa\Framework\Application;
use Marwa\Framework\Config\AppConfig;
use Marwa\Framework\Config\ErrorConfig;
use Marwa\Framework\Supports\Config;
use Psr\Log\LoggerInterface;

final class ErrorHandlerAdapter
{
    private ?ErrorHandler $handler = null;

    public function __construct(
        private Application $app,
        private Config $config,
        private LoggerInterface $logger
    ) {}

    public function boot(): ?ErrorHandler
    {
        if ($this->handler !== null || !class_exists(ErrorHandler::class)) {
            return $this->handler;
        }

        $this->config->loadIfExists(ErrorConfig::KEY . '.php');
        $errorConfig = ErrorConfig::merge($this->app, $this->config->getArray(ErrorConfig::KEY, []));

        if (!$errorConfig['enabled']) {
            return null;
        }

        $this->handler = new ErrorHandler(
            appName: $errorConfig['appName'],
            env: $errorConfig['environment'],
            logger: $errorConfig['useLogger'] ? $this->logger : null,
            debugbar: $this->resolveDebugReporter($errorConfig['useDebugReporter']),
            renderer: $this->resolveRenderer($errorConfig['renderer'])
        );
        $this->handler->register();

        return $this->handler;
    }

    public function handler(): ?ErrorHandler
    {
        return $this->handler;
    }

    private function resolveDebugReporter(bool $useDebugReporter): mixed
    {
        if (!$useDebugReporter
            || !$this->config->getBool(AppConfig::KEY . '.debugbar', AppConfig::defaults()['debugbar'])) {
            return null;
        }

        try {
            return $this->app->make('debugbar');
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveRenderer(?string $rendererClass): RendererInterface
    {
        if (is_string($rendererClass)
            && class_exists($rendererClass)
            && is_subclass_of($rendererClass, RendererInterface::class)) {
            /** @var RendererInterface $renderer */
            $renderer = new $rendererClass();

            return $renderer;
        }

        return new FallbackRenderer();
    }
}
