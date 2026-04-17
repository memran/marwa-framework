<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Adapters\Event\ErrorHandlerBootstrapped;
use Marwa\Framework\Application;

final class ErrorHandlerBootstrapper
{
    private bool $earlyBooted = false;
    private bool $configured = false;

    public function __construct(
        private Application $app,
        private ErrorHandlerAdapter $adapter
    ) {}

    public function bootstrapEarly(): void
    {
        if ($this->earlyBooted) {
            return;
        }

        $handler = $this->adapter->bootEarly();
        $this->app->dispatch(new ErrorHandlerBootstrapped(enabled: $handler !== null));
        $this->earlyBooted = true;
    }

    public function bootstrap(): void
    {
        if ($this->configured) {
            return;
        }

        if (!$this->earlyBooted) {
            $this->bootstrapEarly();
        }

        $this->adapter->boot();
        $this->configured = true;
    }
}
