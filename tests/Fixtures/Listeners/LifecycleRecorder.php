<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Listeners;

final class LifecycleRecorder
{
    /**
     * @var list<string>
     */
    public static array $events = [];

    public static function reset(): void
    {
        self::$events = [];
    }

    public static function record(object $event): void
    {
        self::$events[] = $event::class;
    }
}
