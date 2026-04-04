<?php

declare(strict_types=1);

namespace Marwa\Framework\Queue;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Mail\Mailable;

final class MailJob
{
    public const NAME = 'mail:send';

    /**
     * @var array{class: class-string, data: array<string, mixed>}
     */
    private array $payload;

    /**
     * @param array{class: class-string, data: array<string, mixed>} $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return array{class: class-string, data: array<string, mixed>}
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * @param array{class?: class-string, data?: array<string, mixed>} $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self([
            'class' => (string) ($payload['class'] ?? Mailable::class),
            'data' => is_array($payload['data'] ?? null) ? $payload['data'] : [],
        ]);
    }

    public function handle(Application $app): int
    {
        $class = $this->payload['class'];

        if (!class_exists($class) || !is_subclass_of($class, Mailable::class)) {
            throw new \RuntimeException(sprintf('Mail job class [%s] is not a valid mailable.', $class));
        }

        /** @var Mailable $mailable */
        $mailable = new $class($this->payload['data']);
        /** @var MailerInterface $mailer */
        $mailer = $app->mailer();

        return $mailable->build($mailer)->send();
    }
}
