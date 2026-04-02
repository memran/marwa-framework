<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use League\Container\Container;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Marwa\Framework\Supports\Config;
use PHPUnit\Framework\TestCase;

final class EventDispatcherAdapterTest extends TestCase
{
    public function testItPassesListenerPrioritiesToMarwaEvent(): void
    {
        $configDir = sys_get_temp_dir() . '/marwa-event-adapter-' . bin2hex(random_bytes(6));
        mkdir($configDir, 0777, true);

        $adapter = new EventDispatcherAdapter(new Container(), new Config($configDir));
        $calls = [];

        $adapter->listen(TestEvent::class, static function (TestEvent $event) use (&$calls): void {
            $calls[] = 'low';
        }, 10);

        $adapter->listen(TestEvent::class, static function (TestEvent $event) use (&$calls): void {
            $calls[] = 'high';
        }, 100);

        $event = new TestEvent();
        self::assertSame($event, $adapter->dispatch($event));
        self::assertSame(['high', 'low'], $calls);

        @rmdir($configDir);
    }
}

final class TestEvent {}
