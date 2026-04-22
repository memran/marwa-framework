<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
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
    'enabled' => false,
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
];
PHP
        );

        $this->app = new Application($this->basePath);
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/.env');
        @unlink($this->basePath . '/config/mail.php');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app']);
    }

    public function testValidEmailPassesValidation(): void
    {
        $config = $this->app->make(Config::class);
        $mailer = new Mailer($this->app, $config);

        $this->expectNotToPerformAssertions();
        $mailer->to('valid@example.com');
    }

    public function testInvalidEmailThrowsException(): void
    {
        $config = $this->app->make(Config::class);
        $mailer = new Mailer($this->app, $config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: invalid-email');
        $mailer->to('invalid-email');
    }

    public function testInvalidEmailInArrayThrowsException(): void
    {
        $config = $this->app->make(Config::class);
        $mailer = new Mailer($this->app, $config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: bad@');
        $mailer->to(['user@example.com', 'bad@']);
    }

    public function testInvalidEmailInAssociativeArrayThrowsException(): void
    {
        $config = $this->app->make(Config::class);
        $mailer = new Mailer($this->app, $config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: invalid@domain');
        $mailer->to(['invalid@domain' => 'Name']);
    }
}