<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Psr\Http\Message\{ServerRequestInterface};

interface HttpFactoryInterface
{
    public function request(): ServerRequestInterface;
}
