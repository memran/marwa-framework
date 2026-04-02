<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use Marwa\Framework\Contracts\HttpFactoryInterface;
use Marwa\Router\Http\RequestFactory;
use Psr\Http\Message\{ServerRequestInterface};

/**
 * Thin wrapper over Diactoros factories, using Laravel-style method names.
 */
final class HttpRequestFactory implements HttpFactoryInterface
{
    public function request(): ServerRequestInterface
    {
        return RequestFactory::fromGlobals();
    }
}
