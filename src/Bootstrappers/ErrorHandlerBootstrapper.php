<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Framework\Adapters\ErrorHandlerAdapter;

final class ErrorHandlerBootstrapper
{
    private bool $booted = false;

    public function __construct(private ErrorHandlerAdapter $adapter) {}

    public function bootstrap(): void
    {
        if ($this->booted) {
            return;
        }

        $this->adapter->boot();
        $this->booted = true;
    }
}
