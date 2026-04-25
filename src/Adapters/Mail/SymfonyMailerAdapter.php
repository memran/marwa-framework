<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Mail;

use Marwa\Framework\Application;
use Marwa\Framework\Config\MailConfig;
use Marwa\Framework\Contracts\LoggerInterface;
use Marwa\Framework\Contracts\MailerAdapterInterface;
use Marwa\Framework\Supports\Config;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

final class SymfonyMailerAdapter implements MailerAdapterInterface
{
    private ?TransportInterface $transport = null;
    private ?SymfonyMailer $symfonyMailer = null;
    private ?string $transportConfigHash = null;

    public function __construct(
        private Application $app,
        private Config $config
    ) {}

    public function send(Email $email): int
    {
        $settings = $this->configuration();
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

        try {
            $this->getSymfonyMailer()->send($email);

            if ($logger !== null) {
                $logger->info('Email sent successfully', [
                    'subject' => $subject,
                    'sent' => $recipientCount,
                    'recipients' => $recipientCount,
                ]);
            }

            return $recipientCount;
        } catch (\Symfony\Component\Mailer\Exception\ExceptionInterface $e) {
            if ($logger !== null) {
                $logger->error('Email send failed: transport error', [
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                ]);
            }
            throw new \Marwa\Framework\Exceptions\MailSendException('Failed to send email: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            if ($logger !== null) {
                $logger->error('Email send failed: transport error', [
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                ]);
            }
            throw new \Marwa\Framework\Exceptions\MailSendException('Failed to send email due to transport error: ' . $e->getMessage(), 0, $e);
        }
    }

    public function transport(): TransportInterface
    {
        $settings = $this->configuration();
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

    public function configuration(): array
    {
        $this->config->loadIfExists(MailConfig::KEY . '.php');
        $settings = MailConfig::merge($this->app, $this->config->getArray(MailConfig::KEY, []));

        return $settings;
    }

    public function getSymfonyMailer(): SymfonyMailer
    {
        if ($this->symfonyMailer === null) {
            $this->symfonyMailer = new SymfonyMailer($this->transport());
        }

        return $this->symfonyMailer;
    }

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

    private function transportConfigHash(array $settings): string
    {
        return hash('sha256', json_encode([
            'driver' => $settings['driver'],
            'smtp' => $settings['smtp'],
            'sendmail' => $settings['sendmail'],
        ], JSON_THROW_ON_ERROR));
    }
}