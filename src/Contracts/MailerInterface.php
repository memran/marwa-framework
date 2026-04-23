<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface MailerInterface
{
    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     charset: string,
     *     from: array{address: string, name: string},
     *     smtp: array{
     *         host: string,
     *         port: int,
     *         encryption: string|null,
     *         username: string|null,
     *         password: string|null,
     *         authMode: string|null,
     *         timeout: int
     *     },
     *     sendmail: array{path: string}
     * }
     */
    public function configuration(): array;

    public function reset(): self;

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function from(string|array $address, ?string $name = null): self;

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function to(string|array $address, ?string $name = null): self;

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function cc(string|array $address, ?string $name = null): self;

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function bcc(string|array $address, ?string $name = null): self;

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function replyTo(string|array $address, ?string $name = null): self;

    public function subject(string $subject): self;

    public function text(string $text): self;

    public function html(string $html, ?string $text = null): self;

    public function attach(string $path, ?string $name = null, string $mime = 'application/octet-stream'): self;

    public function attachData(string $data, string $name, string $mime = 'application/octet-stream'): self;

    public function message(): object;

    public function transport(): object;

    public function send(?callable $callback = null): int;

    public function queue(\Marwa\Framework\Mail\Mailable $mailable, ?string $queue = null, int $delaySeconds = 0): \Marwa\Framework\Queue\QueuedJob;

    /**
     * Queue email to be sent at a specific timestamp
     */
    public function queueAt(\Marwa\Framework\Mail\Mailable $mailable, ?string $queue = null, int $timestamp): \Marwa\Framework\Queue\QueuedJob;

    /**
     * Queue recurring email
     * @param array{expression: string, timezone?: string} $schedule
     */
    public function queueRecurring(\Marwa\Framework\Mail\Mailable $mailable, ?string $queue = null, array $schedule): \Marwa\Framework\Queue\QueuedJob;
}
