<?php

declare(strict_types=1);

namespace Marwa\Framework\Views\Extension;

use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Supports\Storage;

final class MailViewExtension extends AbstractViewExtension
{
    public function register(): void
    {
        $this->addFunction('send_email', [$this, 'sendEmail']);
    }

    /**
     * @param array{
     *     to: string|array,
     *     subject: string,
     *     body?: string,
     *     html?: string,
     *     from?: string|array,
     *     cc?: string|array,
     *     bcc?: string|array,
     *     reply_to?: string|array
     * } $options
     */
    public function sendEmail(array $options): bool
    {
        $app = app();
        $mailer = $app->make(MailerInterface::class);

        if (isset($options['from'])) {
            $mailer->from($options['from']);
        }

        $mailer->to($options['to'])
            ->subject($options['subject']);

        if (isset($options['html'])) {
            $mailer->html($options['html']);
        } elseif (isset($options['body'])) {
            $mailer->text($options['body']);
        }

        if (isset($options['cc'])) {
            $mailer->cc($options['cc']);
        }

        if (isset($options['bcc'])) {
            $mailer->bcc($options['bcc']);
        }

        if (isset($options['reply_to'])) {
            $mailer->replyTo($options['reply_to']);
        }

        return $mailer->send() > 0;
    }
}