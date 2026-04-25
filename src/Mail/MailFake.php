<?php

declare(strict_types=1);

namespace Marwa\Framework\Mail;

use Marwa\Framework\Contracts\MailerAdapterInterface;
use Symfony\Component\Mime\Email;

final class MailFake implements MailerAdapterInterface
{
    /**
     * @var list<Email>
     */
    private array $sentEmails = [];

    /**
     * @var array<string, Email>
     */
    private array $queuedEmails = [];

    public function __construct(
        private ?MailerAdapterInterface $realAdapter = null
    ) {}

    public function send(Email $email): int
    {
        $this->sentEmails[] = $email;

        if ($this->realAdapter !== null) {
            return $this->realAdapter->send($email);
        }

        $recipients = count($email->getTo()) + count($email->getCc()) + count($email->getBcc());

        return $recipients;
    }

    public function transport(): \Symfony\Component\Mailer\Transport\TransportInterface
    {
        if ($this->realAdapter !== null) {
            return $this->realAdapter->transport();
        }

        return new \Symfony\Component\Mailer\Transport\InMemoryTransport();
    }

    public function configuration(): array
    {
        if ($this->realAdapter !== null) {
            return $this->realAdapter->configuration();
        }

        return [
            'enabled' => true,
            'driver' => 'memory',
            'charset' => 'UTF-8',
            'from' => ['address' => 'test@example.com', 'name' => 'Test'],
            'smtp' => ['host' => 'localhost', 'port' => 25, 'encryption' => null, 'username' => null, 'password' => null, 'authMode' => null, 'timeout' => 30],
            'sendmail' => ['path' => '/usr/sbin/sendmail'],
        ];
    }

    public function getSymfonyMailer(): \Symfony\Component\Mailer\Mailer
    {
        if ($this->realAdapter !== null) {
            return $this->realAdapter->getSymfonyMailer();
        }

        return new \Symfony\Component\Mailer\Mailer($this->transport());
    }

    /**
     * @return list<Email>
     */
    public function sentEmails(): array
    {
        return $this->sentEmails;
    }

    /**
     * @return list<Email>
     */
    public function queuedEmails(): array
    {
        return $this->queuedEmails;
    }

    public function hasSent(string $search): bool
    {
        foreach ($this->sentEmails as $email) {
            $subject = $email->getSubject() ?? '';
            if (stripos($subject, $search) !== false) {
                return true;
            }
        }

        return false;
    }

    public function countSent(): int
    {
        return count($this->sentEmails);
    }

    public function lastSent(): ?Email
    {
        return $this->sentEmails[array_key_last($this->sentEmails)] ?? null;
    }

    public function reset(): self
    {
        $this->sentEmails = [];
        $this->queuedEmails = [];

        return $this;
    }
}