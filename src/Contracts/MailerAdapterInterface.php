<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface MailerAdapterInterface
{
    public function send(\Symfony\Component\Mime\Email $email): int;

    public function transport(): \Symfony\Component\Mailer\Transport\TransportInterface;

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array;

    public function getSymfonyMailer(): \Symfony\Component\Mailer\Mailer;
}
