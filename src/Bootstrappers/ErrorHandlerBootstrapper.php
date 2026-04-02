<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Adapters\Event\ErrorHandlerBootstrapped;
use Marwa\Framework\Application;

final class ErrorHandlerBootstrapper
{
    private bool $booted = false;

    public function __construct(
        private Application $app,
        private ErrorHandlerAdapter $adapter
    ) {}

    public function bootstrap(): void
    {
        if ($this->booted) {
            return;
        }

        $handler = $this->adapter->boot();
        $this->app->dispatch(new ErrorHandlerBootstrapped(enabled: $handler !== null));
        $this->booted = true;
    }
}
