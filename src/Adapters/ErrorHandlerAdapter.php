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
    private bool $registered = false;

    public function __construct(
        private Application $app,
        private Config $config,
        private LoggerInterface $logger
    ) {}

    public function boot(): ?ErrorHandler
    {
        if (!class_exists(ErrorHandler::class)) {
            return $this->handler;
        }

        $this->config->loadIfExists(ErrorConfig::KEY . '.php');
        $errorConfig = ErrorConfig::merge($this->app, $this->config->getArray(ErrorConfig::KEY, []));

        if (!$errorConfig['enabled']) {
            $this->disable();

            return null;
        }

        if ($this->handler === null) {
            $this->handler = $this->createHandler(
                $errorConfig['appName'],
                $errorConfig['environment'],
                $errorConfig['useLogger'] ? $this->logger : null,
                $errorConfig['useDebugReporter'] ? $this->resolveDebugReporter(true) : null,
                $this->resolveRenderer($errorConfig['renderer'])
            );
            $this->registered = true;
        } else {
            $this->handler
                ->setLogger($errorConfig['useLogger'] ? $this->logger : null)
                ->setDebugbar($errorConfig['useDebugReporter'] ? $this->resolveDebugReporter(true) : null)
                ->setRenderer($this->resolveRenderer($errorConfig['renderer']));
        }

        return $this->handler;
    }

    public function bootEarly(): ?ErrorHandler
    {
        if ($this->registered || !class_exists(ErrorHandler::class)) {
            return $this->handler;
        }

        $defaults = ErrorConfig::defaults($this->app);
        $this->handler = $this->createHandler(
            $defaults['appName'],
            $defaults['environment'],
            null,
            null,
            new FallbackRenderer()
        );
        $this->registered = true;

        return $this->handler;
    }

    public function handler(): ?ErrorHandler
    {
        return $this->handler;
    }

    private function disable(): void
    {
        if ($this->handler === null) {
            return;
        }

        restore_error_handler();
        restore_exception_handler();
        $this->handler = null;
        $this->registered = false;
    }

    private function createHandler(
        string $appName,
        string $environment,
        ?LoggerInterface $logger,
        mixed $debugbar,
        ?RendererInterface $renderer
    ): ErrorHandler {
        $handler = new ErrorHandler(
            appName: $appName,
            env: $environment,
            logger: $logger,
            debugbar: $debugbar,
            renderer: $renderer
        );
        $handler->register();

        return $handler;
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
