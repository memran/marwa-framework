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
