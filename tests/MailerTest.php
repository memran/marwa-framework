<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Adapters\Mail\SymfonyMailerAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Mail\Mailable;
use Marwa\Framework\Mail\MailFake;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Supports\Mailer;
use PHPUnit\Framework\TestCase;

final class MailerTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-mailer-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents(
            $this->basePath . '/config/mail.php',
            <<<'PHP'
<?php

return [
    'enabled' => true,
    'driver' => 'mail',
    'charset' => 'UTF-8',
    'from' => [
        'address' => 'test@example.com',
        'name' => 'Test',
    ],
    'smtp' => [
        'host' => 'localhost',
        'port' => 25,
        'encryption' => null,
        'username' => null,
        'password' => null,
        'authMode' => null,
        'timeout' => 30,
    ],
    'sendmail' => [
        'path' => '/usr/sbin/sendmail',
    ],
    'template' => [
        'path' => 'resources/views/emails',
        'autoPlainText' => true,
        'inlineCss' => true,
    ],
];
PHP
        );

        $this->app = new Application($this->basePath);
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/.env');
        @unlink($this->basePath . '/config/mail.php');
        $this->removeDirectory($this->basePath . '/resources');
        $this->removeDirectory($this->basePath . '/config');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app']);
    }

    public function testValidEmailPassesValidation(): void
    {
        $config = $this->app->make(Config::class);
        $adapter = new SymfonyMailerAdapter($this->app, $config);
        $mailer = new Mailer($this->app, $adapter);

        $this->expectNotToPerformAssertions();
        $mailer->to('valid@example.com');
    }

    public function testInvalidEmailThrowsException(): void
    {
        $config = $this->app->make(Config::class);
        $adapter = new SymfonyMailerAdapter($this->app, $config);
        $mailer = new Mailer($this->app, $adapter);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: invalid-email');
        $mailer->to('invalid-email');
    }

    public function testInvalidEmailInArrayThrowsException(): void
    {
        $config = $this->app->make(Config::class);
        $adapter = new SymfonyMailerAdapter($this->app, $config);
        $mailer = new Mailer($this->app, $adapter);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: bad@');
        $mailer->to(['user@example.com', 'bad@']);
    }

    public function testInvalidEmailInAssociativeArrayThrowsException(): void
    {
        $config = $this->app->make(Config::class);
        $adapter = new SymfonyMailerAdapter($this->app, $config);
        $mailer = new Mailer($this->app, $adapter);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: invalid@domain');
        $mailer->to(['invalid@domain' => 'Name']);
    }

    public function testSharedMailerReadsUpdatedGlobalConfig(): void
    {
        /** @var Mailer $mailer */
        $mailer = $this->app->mailer();

        self::assertSame('mail', $mailer->configuration()['driver']);
        self::assertSame('test@example.com', $mailer->configuration()['from']['address']);

        /** @var Config $config */
        $config = $this->app->make(Config::class);
        $config->set('mail.driver', 'sendmail');
        $config->set('mail.from.address', 'updated@example.com');
        $config->set('mail.from.name', 'Updated');
        $config->set('mail.sendmail.path', '/usr/local/bin/sendmail -bs');

        self::assertSame('sendmail', $mailer->configuration()['driver']);
        self::assertSame('updated@example.com', $mailer->configuration()['from']['address']);
        self::assertSame('Updated', $mailer->configuration()['from']['name']);
        self::assertSame('/usr/local/bin/sendmail -bs', $mailer->configuration()['sendmail']['path']);
    }

    public function testMailerHtmlTemplateUsesConfiguredTemplatePath(): void
    {
        mkdir($this->basePath . '/resources/views/mail-emails', 0777, true);
        file_put_contents(
            $this->basePath . '/resources/views/mail-emails/welcome.twig',
            '<p>Hello {{ name }}</p>'
        );

        $this->writeMailConfig('resources/views/mail-emails');
        unset($GLOBALS['marwa_app']);
        $app = new Application($this->basePath);

        /** @var Mailer $mailer */
        $mailer = $app->mailer();
        $mailer->htmlTemplate('welcome', ['name' => 'Alice']);

        $message = $mailer->message();

        self::assertSame('<p>Hello Alice</p>', trim((string) $message->getHtmlBody()));
    }

    public function testMailableSendUsesRenderedTemplateBody(): void
    {
        mkdir($this->basePath . '/resources/views/emails', 0777, true);
        file_put_contents(
            $this->basePath . '/resources/views/emails/welcome.twig',
            '<p>Hello {{ name }}</p>'
        );

        $this->writeMailConfig('resources/views/emails');
        unset($GLOBALS['marwa_app']);
        $app = new Application($this->basePath);

        $fake = new MailFake();
        $mailer = new Mailer($app, $fake);
        $app->container()->addShared(MailerInterface::class, $mailer, true);
        $app->container()->addShared(Mailer::class, $mailer, true);
        self::assertSame($mailer, app(MailerInterface::class));

        $mailable = new class () extends Mailable {
            public function build(MailerInterface $mailer): MailerInterface
            {
                return $mailer
                    ->subject('Welcome')
                    ->to('alice@example.com');
            }
        };

        $sent = $mailable->htmlTemplate('welcome', ['name' => 'Alice'])->send();

        self::assertSame(1, $sent);
        self::assertCount(1, $fake->sentEmails());
        self::assertSame('<p>Hello Alice</p>', trim((string) $fake->lastSent()->getHtmlBody()));
        self::assertSame('Hello Alice', trim((string) $fake->lastSent()->getTextBody()));
    }

    private function writeMailConfig(string $templatePath): void
    {
        file_put_contents(
            $this->basePath . '/config/mail.php',
            '<?php

return [
    "enabled" => true,
    "driver" => "mail",
    "charset" => "UTF-8",
    "from" => [
        "address" => "test@example.com",
        "name" => "Test",
    ],
    "smtp" => [
        "host" => "localhost",
        "port" => 25,
        "encryption" => null,
        "username" => null,
        "password" => null,
        "authMode" => null,
        "timeout" => 30,
    ],
    "sendmail" => [
        "path" => "/usr/sbin/sendmail",
    ],
    "template" => [
        "path" => "' . $templatePath . '",
        "autoPlainText" => true,
        "inlineCss" => true,
    ],
];
'
        );
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $current = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($current)) {
                $this->removeDirectory($current);
                continue;
            }

            @unlink($current);
        }

        @rmdir($path);
    }
}
