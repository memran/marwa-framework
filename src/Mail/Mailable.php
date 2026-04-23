<?php

declare(strict_types=1);

namespace Marwa\Framework\Mail;

use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Queue\QueuedJob;

abstract class Mailable
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function with(array $data): static
    {
        /** @var array<string, mixed> $newData */
        $newData = array_replace($this->data, $data);

        return new static($newData);
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @param mixed $default
     */
    public function value(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    abstract public function build(MailerInterface $mailer): MailerInterface;

    public function send(): int
    {
        /** @var MailerInterface $mailer */
        $mailer = app(MailerInterface::class);

        return $this->build($mailer)->send();
    }

    public function queue(?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        /** @var MailerInterface $mailer */
        $mailer = app(MailerInterface::class);

        return $mailer->queue($this, $queue, $delaySeconds);
    }

    /**
     * Queue email to be sent at a specific timestamp
     */
    public function queueAt(int $timestamp, ?string $queue = null): QueuedJob
    {
        /** @var MailerInterface $mailer */
        $mailer = app(MailerInterface::class);

        return $mailer->queueAt($this, $timestamp, $queue);
    }

    /**
     * Queue recurring email
     * @param array{expression: string, timezone?: string} $schedule
     */
    public function queueRecurring(array $schedule, ?string $queue = null): QueuedJob
    {
        /** @var MailerInterface $mailer */
        $mailer = app(MailerInterface::class);

        return $mailer->queueRecurring($this, $schedule, $queue);
    }

    /**
     * @return array{class: class-string, data: array<string, mixed>}
     */
    public function toQueuePayload(): array
    {
        return [
            'class' => static::class,
            'data' => $this->data,
        ];
    }
}
