<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Adapters\Event\AppBooted;
use Marwa\Framework\Adapters\Event\AppTerminated;
use PHPUnit\Framework\TestCase;

final class EventLifecycleTest extends TestCase
{
    public function testBootEventInitializesItsInheritedName(): void
    {
        $event = new AppBooted(environment: 'testing', basePath: '/tmp/app');

        self::assertSame(AppBooted::class, $event->getName());
    }

    public function testTerminateEventInitializesItsInheritedName(): void
    {
        $event = new AppTerminated(statusCode: 200);

        self::assertSame(AppTerminated::class, $event->getName());
    }
}
