<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

final class NatsBucketEntry
{
    public function __construct(
        public readonly string $value,
        public readonly int $revision
    ) {}
}
