<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface KafkaConsumerInterface
{
    /**
     * Consume messages from the given topics.
     *
     * The handler receives a normalized message array and the topic name.
     *
     * @param list<string> $topics
     * @param callable(array<string, mixed>, string): mixed $handler
     * @param array<string, mixed> $options
     */
    public function consume(array $topics, callable $handler, array $options = []): int;
}
