<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Mail;

use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Mail\Mailable;

final class WelcomeMail extends Mailable
{
    public function build(MailerInterface $mailer): MailerInterface
    {
        return $mailer
            ->subject((string) $this->value('subject', 'Welcome'))
            ->to($this->value('to', []))
            ->html((string) $this->value('html', '<p>Welcome</p>'), (string) $this->value('text', 'Welcome'));
    }
}
