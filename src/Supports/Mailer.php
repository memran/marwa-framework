<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\MailerAdapterInterface;
use Marwa\Framework\Mail\Mailable;
use Marwa\Framework\Queue\MailJob;
use Marwa\Framework\Queue\QueuedJob;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class Mailer implements \Marwa\Framework\Contracts\MailerInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $from = [];
    /**
     * @var array{
     *     to: array<string, string|null>,
     *     cc: array<string, string|null>,
     *     bcc: array<string, string|null>,
     *     replyTo: array<string, string|null>
     * }
     */
    private array $recipients = ['to' => [], 'cc' => [], 'bcc' => [], 'replyTo' => []];
    private ?string $subject = null;
    private ?string $htmlBody = null;
    private ?string $textBody = null;
    /**
     * @var list<array{type: 'path'|'data', value: string, name: string|null, mime: string}>
     */
    private array $attachments = [];

    public function __construct(
        private Application $app,
        private MailerAdapterInterface $adapter
    ) {}

    public function configuration(): array
    {
        return $this->adapter->configuration();
    }

    public function reset(): self
    {
        $this->from = [];
        $this->recipients = ['to' => [], 'cc' => [], 'bcc' => [], 'replyTo' => []];
        $this->subject = null;
        $this->htmlBody = null;
        $this->textBody = null;
        $this->attachments = [];

        return $this;
    }

    public function from(string|array $address, ?string $name = null): self
    {
        $this->from = $this->normalizeRecipients($address, $name);

        return $this;
    }

    public function to(string|array $address, ?string $name = null): self
    {
        $this->recipients['to'] = $this->mergeRecipients($this->recipients['to'], $this->normalizeRecipients($address, $name));

        return $this;
    }

    public function cc(string|array $address, ?string $name = null): self
    {
        $this->recipients['cc'] = $this->mergeRecipients($this->recipients['cc'], $this->normalizeRecipients($address, $name));

        return $this;
    }

    public function bcc(string|array $address, ?string $name = null): self
    {
        $this->recipients['bcc'] = $this->mergeRecipients($this->recipients['bcc'], $this->normalizeRecipients($address, $name));

        return $this;
    }

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

        return $this->html($viewResponse->getBody()->__toString());
    }

    public function attach(string $path, ?string $name = null, string $mime = 'application/octet-stream'): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('Attachment [%s] does not exist or is not readable.', $path));
        }

        $this->attachments[] = ['type' => 'path', 'value' => $path, 'name' => $name, 'mime' => $mime];

        return $this;
    }

    public function attachData(string $data, string $name, string $mime = 'application/octet-stream'): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Attachment name cannot be empty.');
        }

        $this->attachments[] = ['type' => 'data', 'value' => $data, 'name' => $name, 'mime' => $mime];

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
        $settings = $this->configuration();
        $fromAddress = $this->from !== [] ? $this->from : $settings['from'];
        $email = (new Email())
            ->from(new Address($fromAddress['address'], $fromAddress['name'] ?? ''))
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
     * @return list<string|Address>
     */
    private function formatAddresses(array $addresses): array
    {
        $result = [];
        foreach ($addresses as $email => $name) {
            $result[] = $name !== null && $name !== '' ? new Address($email, $name) : $email;
        }

        return $result;
    }

    /**
     * @param array{type: 'path'|'data', value: string, name: string|null, mime: string} $attachment
     */
    private function applyAttachment(Email $email, array $attachment): Email
    {
        return $attachment['type'] === 'path'
            ? $email->attachFromPath($attachment['value'], $attachment['name'] ?? null, $attachment['mime'])
            : $email->attach($attachment['value'], $attachment['name'] ?? null, $attachment['mime']);
    }

    public function send(?callable $callback = null): int
    {
        $settings = $this->configuration();
        if (!$settings['enabled']) {
            throw new \RuntimeException('Mailer service is disabled.');
        }

        $email = $this->message();

        if ($callback !== null) {
            $callback($email, $this);
        }

        $recipientCount = $this->adapter->send($email);
        $this->reset();

        return $recipientCount;
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

    public function queueAt(Mailable $mailable, int $timestamp, ?string $queue = null): QueuedJob
    {
        return $this->app->queue()->pushAt(
            MailJob::NAME,
            $timestamp,
            $mailable->toQueuePayload(),
            $queue
        );
    }

    public function queueRecurring(Mailable $mailable, array $schedule, ?string $queue = null): QueuedJob
    {
        return $this->app->queue()->pushRecurring(
            MailJob::NAME,
            $schedule,
            $mailable->toQueuePayload(),
            $queue
        );
    }

    public function transport(): \Symfony\Component\Mailer\Transport\TransportInterface
    {
        return $this->adapter->transport();
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
            } else {
                $email = trim($key);
                if ($email === '') {
                    continue;
                }
                $this->validateEmail($email);
                $recipients[$email] = $value !== '' ? $value : null;
            }
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

    private function validateEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException("Invalid email address: {$email}");
        }
    }

    private static ?MailerAdapterInterface $swappedAdapter = null;

    public static function swap(MailerAdapterInterface $adapter): void
    {
        self::$swappedAdapter = $adapter;
    }

    public static function getSwappedAdapter(): ?MailerAdapterInterface
    {
        return self::$swappedAdapter;
    }

    public static function restore(): void
    {
        self::$swappedAdapter = null;
    }
}
