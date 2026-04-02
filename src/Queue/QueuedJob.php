<?php

declare(strict_types=1);

namespace Marwa\Framework\Queue;

final class QueuedJob
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private string $id,
        private string $name,
        private string $queue,
        private array $payload,
        private int $attempts,
        private int $availableAt,
        private int $createdAt
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function queue(): string
    {
        return $this->queue;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function availableAt(): int
    {
        return $this->availableAt;
    }

    public function createdAt(): int
    {
        return $this->createdAt;
    }

    public function withAttempts(int $attempts): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->queue,
            $this->payload,
            $attempts,
            $this->availableAt,
            $this->createdAt
        );
    }

    public function withAvailableAt(int $availableAt): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->queue,
            $this->payload,
            $this->attempts,
            $availableAt,
            $this->createdAt
        );
    }

    /**
     * @return array{
     *     id:string,
     *     name:string,
     *     queue:string,
     *     payload:array<string, mixed>,
     *     attempts:int,
     *     availableAt:int,
     *     createdAt:int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'queue' => $this->queue,
            'payload' => $this->payload,
            'attempts' => $this->attempts,
            'availableAt' => $this->availableAt,
            'createdAt' => $this->createdAt,
        ];
    }

    /**
     * @param array{
     *     id?:string,
     *     name?:string,
     *     queue?:string,
     *     payload?:array<string, mixed>,
     *     attempts?:int,
     *     availableAt?:int,
     *     createdAt?:int
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['id'] ?? ''),
            (string) ($data['name'] ?? 'job'),
            (string) ($data['queue'] ?? 'default'),
            is_array($data['payload'] ?? null) ? $data['payload'] : [],
            (int) ($data['attempts'] ?? 0),
            (int) ($data['availableAt'] ?? time()),
            (int) ($data['createdAt'] ?? time())
        );
    }
}
