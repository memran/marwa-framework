<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Error;

use Marwa\ErrorHandler\Contracts\RendererInterface;
use Throwable;

final class SpyRenderer implements RendererInterface
{
    public static int $webCalls = 0;
    public static int $genericCalls = 0;
    public static int $cliCalls = 0;

    public static function reset(): void
    {
        self::$webCalls = 0;
        self::$genericCalls = 0;
        self::$cliCalls = 0;
    }

    public function renderException(Throwable $e, string $appName, bool $dev): void
    {
        self::$webCalls++;
    }

    public function renderGeneric(string $appName): void
    {
        self::$genericCalls++;
    }

    public function renderCli(Throwable $e, string $appName, bool $dev): void
    {
        self::$cliCalls++;
    }
}
