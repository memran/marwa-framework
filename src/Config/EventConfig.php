<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Event\Contracts\Subscriber;

final class EventConfig
{
    public const KEY = 'event';

    /**
     * @return array{
     *     listeners: array<string, list<callable|array<int|string, mixed>|string>>,
     *     subscribers: list<Subscriber|string>
     * }
     */
    public static function defaults(): array
    {
        return [
            'listeners' => [],
            'subscribers' => [],
        ];
    }
}
