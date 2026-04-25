<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

use Marwa\Framework\Application;
use Marwa\Framework\Config\MailConfig;
use Marwa\Framework\Contracts\LoggerInterface;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Exceptions\MailSendException;
use Marwa\Framework\Mail\Mailable;
use Marwa\Framework\Queue\MailJob;
use Marwa\Framework\Queue\QueuedJob;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

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

    private ?TransportInterface $transport = null;
    private ?SymfonyMailer $symfonyMailer = null;
    private ?string $transportConfigHash = null;

    public function __construct(
        private Application $app,
        private Config $config
    ) {}

    public function configuration(): array
    {
        return $this->settings();
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
        $this->transport = null;
        $this->symfonyMailer = null;
        $this->transportConfigHash = null;

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
        if ($text !== null) {
            $this->textBody = $text;
        }

        return $this;
    }

    public function clearTextBody(): self
    {
        $this->textBody = null;

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function htmlTemplate(string $template, array $data = []): self
    {
        $viewResponse = view($template, $data);
        if ($viewResponse === null) {
            throw new \RuntimeException(sprintf('Template [%s] could not be rendered.', $template));
        }

        $html = $viewResponse->getBody()->__toString();

        return $this->html($html);
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

    public function attachFromStorage(string $path, ?string $name = null, ?string $disk = null): self
    {
        if (trim($path) === '') {
            throw new \InvalidArgumentException('Storage path cannot be empty.');
        }

        $storage = $this->app->make(\Marwa\Framework\Supports\Storage::class);
        if ($disk !== null) {
            $storage = $storage->disk($disk);
        }

        if (!$storage->exists($path)) {
            throw new \InvalidArgumentException(sprintf('Storage file [%s] does not exist.', $path));
        }

        $content = $storage->get($path);
        $mime = $storage->mimeType($path) ?: 'application/octet-stream';
        $filename = $name ?? basename($path);

        return $this->attachData($content, $filename, $mime);
    }

    public function message(): Email
    {
        $settings = $this->settings();
        $fromAddress = $this->from !== [] ? $this->from : $settings['from'];
        $email = (new Email())
            ->from(new \Symfony\Component\Mime\Address($fromAddress['address'], $fromAddress['name'] ?? ''))
            ->subject($this->subject ?? '');

        foreach ($this->recipients as $method => $addresses) {
            if ($addresses === []) {
                continue;
            }

            $email = $this->applyRecipients($email, $method, $addresses);
        }

        if ($this->htmlBody !== null) {
            $email->html($this->htmlBody);

            if ($this->textBody !== null) {
                $email->text($this->textBody);
            }
        } elseif ($this->textBody !== null) {
            $email->text($this->textBody);
        }

        foreach ($this->attachments as $attachment) {
            $email = $this->applyAttachment($email, $attachment);
        }

        return $email;
    }

    /**
     * @param array<string, string|null> $addresses
     */
    private function applyRecipients(Email $email, string $method, array $addresses): Email
    {
        return match ($method) {
            'to' => $email->to(...$this->formatAddresses($addresses)),
            'cc' => $email->cc(...$this->formatAddresses($addresses)),
            'bcc' => $email->bcc(...$this->formatAddresses($addresses)),
            'replyTo' => $email->replyTo(...$this->formatAddresses($addresses)),
            default => $email,
        };
    }

    /**
     * @param array<string, string|null> $addresses
     * @return list<string|\Symfony\Component\Mime\Address>
     */
    private function formatAddresses(array $addresses): array
    {
        $result = [];
        foreach ($addresses as $email => $name) {
            if ($name !== null && $name !== '') {
                $result[] = new \Symfony\Component\Mime\Address($email, $name);
            } else {
                $result[] = $email;
            }
        }

        return $result;
    }

    /**
     * @param array{type: string, value: string, name: string|null, mime: string} $attachment
     */
    private function applyAttachment(Email $email, array $attachment): Email
    {
        if ($attachment['type'] === 'path') {
            return $email->attachFromPath($attachment['value'], $attachment['name'] ?? null, $attachment['mime']);
        }

        return $email->attach($attachment['value'], $attachment['name'] ?? null, $attachment['mime']);
    }

    public function transport(): TransportInterface
    {
        $settings = $this->settings();
        $configHash = $this->transportConfigHash($settings);

        if ($this->transport !== null && $this->transportConfigHash === $configHash) {
            return $this->transport;
        }

        $this->transport = match ($settings['driver']) {
            'smtp' => $this->smtpTransport($settings),
            'sendmail' => $this->sendmailTransport($settings),
            'mail' => $this->mailTransport(),
            default => throw new \InvalidArgumentException(sprintf('Mail driver [%s] is not supported.', $settings['driver'])),
        };
        $this->transportConfigHash = $configHash;
        $this->symfonyMailer = null;

        return $this->transport;
    }

    private function getSymfonyMailer(): SymfonyMailer
    {
        if ($this->symfonyMailer === null) {
            $this->symfonyMailer = new SymfonyMailer($this->transport());
        }

        return $this->symfonyMailer;
    }

    public function send(?callable $callback = null): int
    {
        $settings = $this->settings();
        if (!$settings['enabled']) {
            throw new \RuntimeException('Mailer service is disabled.');
        }

        $email = $this->message();
        $recipientCount = count($email->getTo()) + count($email->getCc()) + count($email->getBcc());
        $subject = $email->getSubject() ?? '';

        $logger = $this->app->has(LoggerInterface::class)
            ? $this->app->make(LoggerInterface::class)
            : null;

        if ($logger !== null) {
            $logger->info('Attempting to send email', [
                'subject' => $subject,
                'recipients' => $recipientCount,
            ]);
        }

        if ($callback !== null) {
            $callback($email, $this);
        }

        try {
            $symfonyMailer = $this->getSymfonyMailer();
            $symfonyMailer->send($email);

            if ($logger !== null) {
                $logger->info('Email sent successfully', [
                    'subject' => $subject,
                    'sent' => $recipientCount,
                    'recipients' => $recipientCount,
                ]);
            }

            $this->reset();
            return $recipientCount;
        } catch (\Symfony\Component\Mailer\Exception\ExceptionInterface $e) {
            if ($logger !== null) {
                $logger->error('Email send failed: transport error', [
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                ]);
            }
            throw new MailSendException('Failed to send email: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            if ($logger !== null) {
                $logger->error('Email send failed: transport error', [
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                ]);
            }
            throw new MailSendException('Failed to send email due to transport error: ' . $e->getMessage(), 0, $e);
        }
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
     * Queue email to be sent at a specific timestamp
     */
    public function queueAt(Mailable $mailable, int $timestamp, ?string $queue = null): QueuedJob
    {
        return $this->app->queue()->pushAt(
            MailJob::NAME,
            $timestamp,
            $mailable->toQueuePayload(),
            $queue
        );
    }

    /**
     * Queue recurring email
     * @param array{expression: string, timezone?: string} $schedule
     */
    public function queueRecurring(Mailable $mailable, array $schedule, ?string $queue = null): QueuedJob
    {
        return $this->app->queue()->pushRecurring(
            MailJob::NAME,
            $schedule,
            $mailable->toQueuePayload(),
            $queue
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
            $recipients[$email] = $name;
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

    /**
     * @param array{
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
     * } $settings
     */
    private function smtpTransport(array $settings): TransportInterface
    {
        $smtp = $settings['smtp'];

        $protocol = 'smtp://';
        if ($smtp['encryption'] !== null && $smtp['encryption'] !== '') {
            if (in_array(strtolower($smtp['encryption']), ['ssl', 'tls'])) {
                $protocol = 'smtps://';
            }
        }

        $dsn = sprintf(
            '%s%s:%s@%s:%d',
            $protocol,
            urlencode($smtp['username'] ?? ''),
            urlencode($smtp['password'] ?? ''),
            $smtp['host'],
            $smtp['port']
        );

        if ($smtp['encryption'] !== null && $smtp['encryption'] !== '') {
            $dsn .= '?encryption=' . $smtp['encryption'];
        }

        if ($smtp['authMode'] !== null && $smtp['authMode'] !== '') {
            $dsn .= ($smtp['encryption'] !== null && $smtp['encryption'] !== '' ? '&' : '?') . 'auth_mode=' . $smtp['authMode'];
        }

        return \Symfony\Component\Mailer\Transport::fromDsn($dsn);
    }

    /**
     * @param array{
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
     * } $settings
     */
    private function sendmailTransport(array $settings): TransportInterface
    {
        return \Symfony\Component\Mailer\Transport::fromDsn(
            sprintf('sendmail://%s', $settings['sendmail']['path'])
        );
    }

    private function mailTransport(): TransportInterface
    {
        return \Symfony\Component\Mailer\Transport::fromDsn('native://default');
    }

    private function validateEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException("Invalid email address: {$email}");
        }
    }

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
    private function settings(): array
    {
        $this->config->loadIfExists(MailConfig::KEY . '.php');
        $settings = MailConfig::merge($this->app, $this->config->getArray(MailConfig::KEY, []));
        $this->validateEmail($settings['from']['address']);

        return $settings;
    }

    /**
     * @param array{
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
     * } $settings
     */
    private function transportConfigHash(array $settings): string
    {
        return hash('sha256', json_encode([
            'driver' => $settings['driver'],
            'smtp' => $settings['smtp'],
            'sendmail' => $settings['sendmail'],
        ], JSON_THROW_ON_ERROR));
    }
}
