<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Controllers;

use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

final class CachedRouteController
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    public function show(): ResponseInterface
    {
        return new TextResponse('cached');
    }
}
