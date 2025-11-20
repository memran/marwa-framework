<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

interface LoggerInterface extends PsrLoggerInterface
{
    // Keep PSR-3 methods (`info`, `error`, `debug`, etc.) as-is
}
