<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface KafkaPublisherInterface
{
    /**
     * @param array<string, mixed> $message
     * @param array<string, mixed> $options
     */
    public function publish(string $topic, array $message, array $options = []): mixed;
}
