<?php

declare(strict_types=1);

namespace Marwa\Framework\Queue;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\LoggerInterface;
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
     * @param mixed $payload
     */
    public static function fromArray(mixed $payload): self
    {
        if (!is_array($payload)) {
            $payload = [];
        }

        $className = $payload['class'] ?? null;
        if (!is_string($className) || $className === '') {
            throw new \InvalidArgumentException('Mail job payload class must be a non-empty string.');
        }

        $data = $payload['data'] ?? [];
        if (!is_array($data)) {
            $data = [];
        }

        return new self([
            'class' => $className,
            'data' => $data,
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

        $logger = null;
        if ($app->has(LoggerInterface::class)) {
            /** @var LoggerInterface $logger */
            $logger = $app->make(LoggerInterface::class);
        }

        try {
            if ($logger !== null) {
                $logger->info('Processing mail job', ['class' => $class]);
            }

            $result = $mailable->build($mailer)->send();

            if ($logger !== null) {
                $logger->info('Mail job processed successfully', ['class' => $class]);
            }

            return $result;
        } catch (\Throwable $e) {
            if ($logger !== null) {
                $logger->error('Mail job failed', [
                    'class' => $class,
                    'error' => $e->getMessage(),
                ]);
            }
            throw $e;
        }
    }
}
