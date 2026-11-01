<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Queue\QueuedJob;
use PHPUnit\Framework\TestCase;

final class QueueReservationTokenTest extends TestCase
{
    public function testReservationSurvivesCopiesAndSerialization(): void
    {
        $job = new QueuedJob('id', 'name', 'default', [], 1, 10, 10, 'token');
        self::assertSame('token', $job->withAttempts(2)->reservationToken());
        self::assertSame('token', $job->withAvailableAt(20)->reservationToken());
        self::assertSame('token', QueuedJob::fromArray($job->toArray())->reservationToken());
        self::assertArrayNotHasKey('reservationToken', (new QueuedJob('id', 'name', 'default', [], 0, 10, 10))->toArray());
    }
}
