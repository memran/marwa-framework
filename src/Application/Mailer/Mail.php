<?php

namespace Marwa\Application\Mailer;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;


class Mail
{
    protected static Mailer $mailer;
   
    protected Email $message;

    public function __construct()
    {
        $this->message = new Email();
    }

    public static function init(string $templatePath = null): void
    {
        $dsn = env('MAILER_DSN') ?: 'smtp://localhost'; // Default DSN if not set
        if (empty($dsn)) {
            throw new \RuntimeException('MAILER_DSN environment variable is not set.');
        }
        $transport = Transport::fromDsn($dsn);
        static::$mailer = new Mailer($transport);

    }
    public static function to(string|array $address): static
    {
        $instance = new static();
        $instance->message->to(...self::normalizeAddresses($address));
        return $instance;
    }

    public function cc(string|array $address): static
    {
        $this->message->cc(...self::normalizeAddresses($address));
        return $this;
    }

    public function bcc(string|array $address): static
    {
        $this->message->bcc(...self::normalizeAddresses($address));
        return $this;
    }

    public function from(string $address, ?string $name = null): static
    {
        $this->message->from(new Address($address, $name));
        return $this;
    }

    public function replyTo(string $address, ?string $name = null): static
    {
        $this->message->replyTo(new Address($address, $name));
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->message->subject($subject);
        return $this;
    }

    public function text(string $text): static
    {
        $this->message->text($text);
        return $this;
    }

    public function html(string $html): static
    {
        $this->message->html($html);
        return $this;
    }

    public function view(string $template, array $data = []): static
    {
        if ($template !== null) {
            $html = view($template,$data);
            $this->message->html($html);
        }
        return $this;
    }

    public function attach(string $filePath, ?string $filename = null): static
    {
        $this->message->attachFromPath($filePath, $filename);
        return $this;
    }

    public function send(): void
    {
        static::$mailer->send($this->message);
    }

    protected static function normalizeAddresses(string|array $input): array
    {
        if (is_string($input)) {
            return [new Address($input)];
        }
        return array_map(fn($email) => new Address($email), $input);
    }
    
    public function build(callable|ShouldQueueEmail $mailable): static
    {
        if (is_callable($mailable)) {
            return $mailable($this);
        }

        if ($mailable instanceof ShouldQueueEmail) {
            return $mailable->build($this);
        }

        throw new \InvalidArgumentException('Mailable must be a callable or implement ShouldQueueEmail');
    }
}
