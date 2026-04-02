<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Adapters\Event\AppBooted;
use Marwa\Framework\Adapters\Event\ApplicationBootstrapping;
use Marwa\Framework\Adapters\Event\ApplicationStarted;
use Marwa\Framework\Adapters\Event\AppTerminated;
use Marwa\Framework\Adapters\Event\ConsoleBootstrapped;
use Marwa\Framework\Adapters\Event\ErrorHandlerBootstrapped;
use Marwa\Framework\Adapters\Event\ModulesBootstrapped;
use Marwa\Framework\Adapters\Event\ProvidersBootstrapped;
use Marwa\Framework\Adapters\Event\RequestHandled;
use Marwa\Framework\Adapters\Event\RequestHandlingStarted;
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

    public function testNewLifecycleEventsInitializeTheirInheritedNames(): void
    {
        $events = [
            new ApplicationStarted(environment: 'testing', basePath: '/tmp/app'),
            new ApplicationBootstrapping(basePath: '/tmp/app'),
            new ProvidersBootstrapped(providers: []),
            new ErrorHandlerBootstrapped(enabled: true),
            new ModulesBootstrapped(modules: []),
            new RequestHandlingStarted(method: 'GET', path: '/'),
            new RequestHandled(method: 'GET', path: '/', statusCode: 200),
            new ConsoleBootstrapped(consoleName: 'Console', version: '1.0.0', commandCount: 5),
        ];

        foreach ($events as $event) {
            self::assertSame($event::class, $event->getName());
        }
    }
}
