<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

use Marwa\Framework\Application;
use Marwa\Framework\Config\MailConfig;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Mail\Mailable;
use Marwa\Framework\Queue\MailJob;
use Marwa\Framework\Queue\QueuedJob;

final class Mailer implements MailerInterface
{
    /**
     * @var array{
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
    private array $settings;

    /**
     * @var array<string, string|null>
     */
    private array $from = [];

    /**
     * @var array<string, array<string, string|null>>
     */
    private array $recipients = [
        'to' => [],
        'cc' => [],
        'bcc' => [],
        'replyTo' => [],
    ];

    private ?string $subject = null;
    private ?string $htmlBody = null;
    private ?string $textBody = null;

    /**
     * @var list<array{type: string, value: string, name?: string|null, mime: string}>
     */
    private array $attachments = [];

    private ?object $transport = null;

    public function __construct(
        private Application $app,
        private Config $config
    ) {
        $this->config->loadIfExists(MailConfig::KEY . '.php');
        $this->settings = array_replace_recursive(MailConfig::defaults($this->app), $this->config->getArray(MailConfig::KEY, []));
        $this->settings['enabled'] = (bool) $this->settings['enabled'];
        $this->settings['driver'] = strtolower((string) $this->settings['driver']);
        $this->settings['charset'] = (string) $this->settings['charset'];
        $this->settings['from']['address'] = (string) $this->settings['from']['address'];
        $this->validateEmail($this->settings['from']['address']);
        $this->settings['from']['name'] = (string) $this->settings['from']['name'];
        $this->settings['smtp']['host'] = (string) $this->settings['smtp']['host'];
        $this->settings['smtp']['port'] = (int) $this->settings['smtp']['port'];
        $this->settings['smtp']['timeout'] = (int) $this->settings['smtp']['timeout'];
        $this->settings['sendmail']['path'] = (string) $this->settings['sendmail']['path'];
    }

    public function configuration(): array
    {
        return $this->settings;
    }

    public function reset(): self
    {
        $this->from = [];
        $this->recipients = [
            'to' => [],
            'cc' => [],
            'bcc' => [],
            'replyTo' => [],
        ];
        $this->subject = null;
        $this->htmlBody = null;
        $this->textBody = null;
        $this->attachments = [];

        return $this;
    }

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function from(string|array $address, ?string $name = null): self
    {
        $this->from = $this->normalizeRecipients($address, $name);

        return $this;
    }

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function to(string|array $address, ?string $name = null): self
    {
        $this->recipients['to'] = $this->mergeRecipients($this->recipients['to'], $this->normalizeRecipients($address, $name));

        return $this;
    }

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function cc(string|array $address, ?string $name = null): self
    {
        $this->recipients['cc'] = $this->mergeRecipients($this->recipients['cc'], $this->normalizeRecipients($address, $name));

        return $this;
    }

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function bcc(string|array $address, ?string $name = null): self
    {
        $this->recipients['bcc'] = $this->mergeRecipients($this->recipients['bcc'], $this->normalizeRecipients($address, $name));

        return $this;
    }

    /**
     * @param string|array<string, string>|array<int, string> $address
     */
    public function replyTo(string|array $address, ?string $name = null): self
    {
        $this->recipients['replyTo'] = $this->mergeRecipients($this->recipients['replyTo'], $this->normalizeRecipients($address, $name));

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function text(string $text): self
    {
        $this->textBody = $text;

        return $this;
    }

    public function html(string $html, ?string $text = null): self
    {
        $this->htmlBody = $html;
        $this->textBody = $text ?? $this->textBody;

        return $this;
    }

    public function attach(string $path, ?string $name = null, string $mime = 'application/octet-stream'): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('Attachment [%s] does not exist or is not readable.', $path));
        }

        $this->attachments[] = [
            'type' => 'path',
            'value' => $path,
            'name' => $name,
            'mime' => $mime,
        ];

        return $this;
    }

    public function attachData(string $data, string $name, string $mime = 'application/octet-stream'): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Attachment name cannot be empty.');
        }

        $this->attachments[] = [
            'type' => 'data',
            'value' => $data,
            'name' => $name,
            'mime' => $mime,
        ];

        return $this;
    }

    public function message(): object
    {
        $this->assertSwiftMailerAvailable();

        $message = new \Swift_Message($this->subject ?? '');
        $message = $message->setCharset($this->settings['charset']);
        $message->setFrom($this->from !== [] ? $this->from : $this->settings['from']);

        foreach ($this->recipients as $method => $addresses) {
            if ($addresses === []) {
                continue;
            }

            $message->{$this->recipientMethod($method)}($addresses);
        }

        if ($this->htmlBody !== null) {
            $message->setBody($this->htmlBody, 'text/html', $this->settings['charset']);

            if ($this->textBody !== null) {
                $message->addPart($this->textBody, 'text/plain', $this->settings['charset']);
            }
        } elseif ($this->textBody !== null) {
            $message->setBody($this->textBody, 'text/plain', $this->settings['charset']);
        } else {
            $message->setBody('', 'text/plain', $this->settings['charset']);
        }

        foreach ($this->attachments as $attachment) {
            $message->attach($this->buildAttachment($attachment));
        }

        return $message;
    }

    public function transport(): object
    {
        if ($this->transport !== null) {
            return $this->transport;
        }

        $this->assertSwiftMailerAvailable();

        $this->transport = match ($this->settings['driver']) {
            'smtp' => $this->smtpTransport(),
            'sendmail' => $this->sendmailTransport(),
            'mail' => $this->mailTransport(),
            default => throw new \InvalidArgumentException(sprintf('Mail driver [%s] is not supported.', $this->settings['driver'])),
        };

        return $this->transport;
    }

    public function send(?callable $callback = null): int
    {
        if (!$this->settings['enabled']) {
            throw new \RuntimeException('Mailer service is disabled.');
        }

        $message = $this->message();

        if ($callback !== null) {
            $callback($message, $this);
        }

        $mailer = new \Swift_Mailer($this->transport());
        $sent = $mailer->send($message);
        $this->reset();

        return $sent;
    }

    public function queue(Mailable $mailable, ?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        return $this->app->queue()->push(
            MailJob::NAME,
            $mailable->toQueuePayload(),
            $queue,
            $delaySeconds
        );
    }

    /**
     * @param string|array<string, string>|array<int, string> $address
     * @return array<string, string|null>
     */
    private function normalizeRecipients(string|array $address, ?string $name = null): array
    {
        if (is_string($address)) {
            $email = trim($address);

            if ($email !== '') {
                $this->validateEmail($email);
                return [$email => $name];
            }

            return [];
        }

        $recipients = [];

        foreach ($address as $key => $value) {
            if (is_int($key)) {
                $email = trim((string) $value);

                if ($email === '') {
                    continue;
                }

                $this->validateEmail($email);
                $recipients[$email] = null;

                continue;
            }

            $email = trim($key);

            if ($email === '') {
                continue;
            }

            $this->validateEmail($email);
            $recipients[$email] = $value !== '' ? $value : null;
        }

        if ($name !== null && count($recipients) === 1) {
            $email = array_key_first($recipients);
            $recipients[(string) $email] = $name;
        }

        return $recipients;
    }

    /**
     * @param array<string, string|null> $existing
     * @param array<string, string|null> $incoming
     * @return array<string, string|null>
     */
    private function mergeRecipients(array $existing, array $incoming): array
    {
        foreach ($incoming as $email => $recipientName) {
            $existing[$email] = $recipientName;
        }

        return $existing;
    }

    private function recipientMethod(string $method): string
    {
        return match ($method) {
            'replyTo' => 'setReplyTo',
            default => 'set' . ucfirst($method),
        };
    }

    /**
     * @param array{type: string, value: string, name?: string|null, mime: string} $attachment
     */
    private function buildAttachment(array $attachment): object
    {
        $this->assertSwiftMailerAvailable();

        if ($attachment['type'] === 'path') {
            $swiftAttachment = \Swift_Attachment::fromPath($attachment['value'], $attachment['mime']);
            if (is_string($attachment['name']) && $attachment['name'] !== '') {
                $swiftAttachment->setFilename($attachment['name']);
            }

            return $swiftAttachment;
        }

        return \Swift_Attachment::newInstance($attachment['value'], $attachment['name'] ?? null, $attachment['mime']);
    }

    private function smtpTransport(): object
    {
        $smtp = $this->settings['smtp'];
        $transport = \Swift_SmtpTransport::newInstance($smtp['host'], $smtp['port'], $smtp['encryption']);

        if ($smtp['username'] !== null) {
            $transport->setUsername($smtp['username']);
        }

        if ($smtp['password'] !== null) {
            $transport->setPassword($smtp['password']);
        }

        if ($smtp['authMode'] !== null) {
            $transport->setAuthMode($smtp['authMode']);
        }

        if ($smtp['timeout'] > 0) {
            $transport->setTimeout($smtp['timeout']);
        }

        return $transport;
    }

    private function sendmailTransport(): object
    {
        return \Swift_SendmailTransport::newInstance($this->settings['sendmail']['path']);
    }

    private function mailTransport(): object
    {
        return \Swift_MailTransport::newInstance();
    }

    private function validateEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException("Invalid email address: {$email}");
        }
    }

    private function assertSwiftMailerAvailable(): void
    {
        if (!class_exists(\Swift_Mailer::class) || !class_exists(\Swift_Message::class) || !class_exists(\Swift_Attachment::class)) {
            throw new \RuntimeException('SwiftMailer is required to use the Mailer service.');
        }
    }

}
