<?php

declare(strict_types=1);

namespace {
    if (!class_exists(\Swift_Attachment::class)) {
        final class Swift_Attachment
        {
            public function __construct(
                public string $data,
                public ?string $filename = null,
                public string $contentType = 'application/octet-stream'
            ) {}

            public static function fromPath(string $path, ?string $contentType = null): self
            {
                return new self((string) file_get_contents($path), basename($path), $contentType ?? 'application/octet-stream');
            }

            public static function newInstance(string $data, ?string $filename = null, string $contentType = 'application/octet-stream'): self
            {
                return new self($data, $filename, $contentType);
            }

            public function setFilename(string $filename): self
            {
                $this->filename = $filename;

                return $this;
            }

            public function getFilename(): ?string
            {
                return $this->filename;
            }
        }
    }

    if (!class_exists(\Swift_Message::class)) {
        final class Swift_Message
        {
            /**
             * @var array<string, string|null>
             */
            private array $from = [];

            /**
             * @var array<string, string|null>
             */
            private array $to = [];

            /**
             * @var array<string, string|null>
             */
            private array $cc = [];

            /**
             * @var array<string, string|null>
             */
            private array $bcc = [];

            /**
             * @var array<string, string|null>
             */
            private array $replyTo = [];

            /**
             * @var list<object>
             */
            private array $attachments = [];

            /**
             * @var list<array{body: string, contentType: string, charset: string|null}>
             */
            private array $parts = [];

            private string $body = '';
            private string $contentType = 'text/plain';

            public function __construct(private string $subject = '') {}

            public function setCharset(string $charset): self
            {
                return $this;
            }

            /**
             * @param array<string, string|null> $from
             */
            public function setFrom(array $from): self
            {
                $this->from = $from;

                return $this;
            }

            /**
             * @param array<string, string|null> $to
             */
            public function setTo(array $to): self
            {
                $this->to = $to;

                return $this;
            }

            /**
             * @param array<string, string|null> $cc
             */
            public function setCc(array $cc): self
            {
                $this->cc = $cc;

                return $this;
            }

            /**
             * @param array<string, string|null> $bcc
             */
            public function setBcc(array $bcc): self
            {
                $this->bcc = $bcc;

                return $this;
            }

            /**
             * @param array<string, string|null> $replyTo
             */
            public function setReplyTo(array $replyTo): self
            {
                $this->replyTo = $replyTo;

                return $this;
            }

            public function setSubject(string $subject): self
            {
                $this->subject = $subject;

                return $this;
            }

            public function setBody(string $body, string $contentType = 'text/plain', ?string $charset = null): self
            {
                $this->body = $body;
                $this->contentType = $contentType;

                return $this;
            }

            public function addPart(string $body, string $contentType = 'text/plain', ?string $charset = null): self
            {
                $this->parts[] = compact('body', 'contentType', 'charset');

                return $this;
            }

            public function attach(object $attachment): self
            {
                $this->attachments[] = $attachment;

                return $this;
            }

            public function getSubject(): string
            {
                return $this->subject;
            }

            /**
             * @return array<string, string|null>
             */
            public function getFrom(): array
            {
                return $this->from;
            }

            /**
             * @return array<string, string|null>
             */
            public function getTo(): array
            {
                return $this->to;
            }

            /**
             * @return array<string, string|null>
             */
            public function getCc(): array
            {
                return $this->cc;
            }

            /**
             * @return array<string, string|null>
             */
            public function getBcc(): array
            {
                return $this->bcc;
            }

            /**
             * @return array<string, string|null>
             */
            public function getReplyTo(): array
            {
                return $this->replyTo;
            }

            public function getBody(): string
            {
                return $this->body;
            }

            public function getContentType(): string
            {
                return $this->contentType;
            }

            /**
             * @return list<object>
             */
            public function getAttachments(): array
            {
                return $this->attachments;
            }

            /**
             * @return list<array{body: string, contentType: string, charset: string|null}>
             */
            public function getParts(): array
            {
                return $this->parts;
            }
        }
    }

    if (!class_exists(\Swift_SmtpTransport::class)) {
        final class Swift_SmtpTransport
        {
            public ?string $username = null;
            public ?string $password = null;
            public ?string $authMode = null;
            public ?int $timeout = null;

            public function __construct(
                public string $host,
                public int $port,
                public ?string $encryption = null
            ) {}

            public static function newInstance(string $host, int $port, ?string $encryption = null): self
            {
                return new self($host, $port, $encryption);
            }

            public function setUsername(string $username): self
            {
                $this->username = $username;

                return $this;
            }

            public function setPassword(string $password): self
            {
                $this->password = $password;

                return $this;
            }

            public function setAuthMode(string $authMode): self
            {
                $this->authMode = $authMode;

                return $this;
            }

            public function setTimeout(int $timeout): self
            {
                $this->timeout = $timeout;

                return $this;
            }
        }
    }

    if (!class_exists(\Swift_SendmailTransport::class)) {
        final class Swift_SendmailTransport
        {
            public function __construct(public string $path) {}

            public static function newInstance(string $path): self
            {
                return new self($path);
            }
        }
    }

    if (!class_exists(\Swift_MailTransport::class)) {
        final class Swift_MailTransport
        {
            public static function newInstance(): self
            {
                return new self();
            }
        }
    }

    if (!class_exists(\Swift_Mailer::class)) {
        final class Swift_Mailer
        {
            public ?object $lastMessage = null;

            public function __construct(public object $transport) {}

            public function send(object $message): int
            {
                $this->lastMessage = $message;

                if (method_exists($message, 'getTo')) {
                    return count($message->getTo());
                }

                return 0;
            }
        }
    }
}

namespace Marwa\Framework\Tests {
    use Marwa\Framework\Application;
    use Marwa\Framework\Config\MailConfig;
    use Marwa\Framework\Contracts\MailerInterface;
    use Marwa\Framework\Queue\MailJob;
    use Marwa\Framework\Tests\Fixtures\Mail\WelcomeMail;
    use PHPUnit\Framework\TestCase;

    /**
 * @group slow
 */
    final class MailerSupportTest extends TestCase
    {
        private string $basePath;

        protected function setUp(): void
        {
            $this->basePath = sys_get_temp_dir() . '/marwa-mailer-' . bin2hex(random_bytes(6));
            mkdir($this->basePath, 0777, true);
            mkdir($this->basePath . '/config', 0777, true);

            file_put_contents(
                $this->basePath . '/config/mail.php',
                <<<PHP
<?php

return [
    'enabled' => true,
    'driver' => 'smtp',
    'charset' => 'UTF-8',
    'from' => [
        'address' => 'no-reply@example.test',
        'name' => 'Marwa Test',
    ],
    'smtp' => [
        'host' => '127.0.0.1',
        'port' => 1025,
        'encryption' => null,
        'username' => null,
        'password' => null,
        'authMode' => null,
        'timeout' => 30,
    ],
    'sendmail' => [
        'path' => '/usr/sbin/sendmail -bs',
    ],
];
PHP
            );

            file_put_contents($this->basePath . '/.env', "APP_ENV=local\nAPP_NAME=MarwaTest\nTIMEZONE=UTC\n");
        }

        protected function tearDown(): void
        {
            @unlink($this->basePath . '/config/mail.php');
            @unlink($this->basePath . '/report.txt');
            @unlink($this->basePath . '/.env');
            @rmdir($this->basePath . '/config');
            @rmdir($this->basePath);

            unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['APP_NAME'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['APP_NAME'], $_SERVER['TIMEZONE']);
        }

        public function testMailerHelperResolvesSharedMailerBinding(): void
        {
            $app = new Application($this->basePath);

            self::assertInstanceOf(MailerInterface::class, $app->mailer());
            self::assertSame($app->mailer(), mailer());
        }

        public function testMailerBuildsSwiftCompatibleMessageAndSendsIt(): void
        {
            $app = new Application($this->basePath);

            $tmpFile = $this->basePath . '/report.txt';
            file_put_contents($tmpFile, 'report-body');

            $message = $app->mailer()
                ->from('billing@example.test', 'Billing')
                ->to(['user@example.test' => 'User'])
                ->cc('copy@example.test', 'Copy')
                ->bcc('hidden@example.test', 'Hidden')
                ->replyTo('reply@example.test', 'Reply')
                ->subject('Welcome')
                ->html('<p>Hello</p>', 'Hello')
                ->attach($tmpFile, 'report.txt', 'text/plain')
                ->attachData('inline-data', 'data.txt', 'text/plain')
                ->message();

            self::assertSame('Welcome', $message->getSubject());
            self::assertSame(['billing@example.test' => 'Billing'], $message->getFrom());
            self::assertSame(['user@example.test' => 'User'], $message->getTo());
            self::assertSame(['copy@example.test' => 'Copy'], $message->getCc());
            self::assertSame(['hidden@example.test' => 'Hidden'], $message->getBcc());
            self::assertSame(['reply@example.test' => 'Reply'], $message->getReplyTo());
            self::assertSame('text/html', $message->getContentType());
            self::assertCount(1, $message->getParts());
            self::assertCount(2, $message->getAttachments());
            self::assertSame('report.txt', $message->getAttachments()[0]->getFilename());
            self::assertSame(1, $app->mailer()->send());
        }

        public function testMailerConfigDefaultsRemainProductionFriendly(): void
        {
            $app = new Application($this->basePath);
            $defaults = MailConfig::defaults($app);

            self::assertTrue($defaults['enabled']);
            self::assertSame('smtp', $defaults['driver']);
            self::assertSame('UTF-8', $defaults['charset']);
        }

        public function testMailerCanQueueAndHydrateAQueuedMailJob(): void
        {
            $app = new Application($this->basePath);
            $mail = new WelcomeMail([
                'subject' => 'Queued Welcome',
                'to' => ['queued@example.test' => 'Queued User'],
                'html' => '<p>Queued mail</p>',
            ]);

            $job = $app->mailer()->queue($mail, 'mail');

            self::assertSame(MailJob::NAME, $job->name());
            self::assertSame('mail', $job->queue());

            $reserved = $app->queue()->pop('mail');

            self::assertNotNull($reserved);
            self::assertSame(MailJob::NAME, $reserved->name());

            $result = MailJob::fromArray($reserved->payload())->handle($app);

            self::assertSame(1, $result);
        }
    }
}
