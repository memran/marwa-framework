<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\SessionInterface;
use Marwa\Framework\Supports\EncryptedSession;
use PHPUnit\Framework\TestCase;

final class SessionSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-session-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_KEY=test-suite-secret\nTIMEZONE=UTC\n");
        file_put_contents(
            $this->basePath . '/config/session.php',
            <<<'PHP'
<?php

return [
    'name' => 'marwa_test_session',
    'secure' => false,
    'sameSite' => 'Strict',
    'autoStart' => false,
];
PHP
        );

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        foreach ([
            $this->basePath . '/config/session.php',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['APP_KEY'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_KEY'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testSessionValuesAreEncryptedAtRestAndReadableThroughTheContract(): void
    {
        $app = new Application($this->basePath);

        /** @var EncryptedSession $session */
        $session = $app->make(SessionInterface::class);
        self::assertInstanceOf(EncryptedSession::class, $session);

        $session->set('user_id', 42);
        $session->set('profile', ['name' => 'Marwa']);

        self::assertTrue($session->has('user_id'));
        self::assertSame(42, $session->get('user_id'));
        self::assertSame(['name' => 'Marwa'], $session->get('profile'));
        self::assertSame(42, session('user_id'));

        $raw = $session->raw();

        self::assertArrayHasKey('user_id', $raw);
        self::assertArrayHasKey('profile', $raw);
        self::assertNotSame('42', $raw['user_id']);
        self::assertStringNotContainsString('Marwa', $raw['profile']);

        $all = $session->all();
        self::assertSame(42, $all['user_id']);
        self::assertSame(['name' => 'Marwa'], $all['profile']);
    }

    public function testSessionInvalidateClearsStoredValuesAndRotatesTheSession(): void
    {
        $app = new Application($this->basePath);

        /** @var EncryptedSession $session */
        $session = $app->make(SessionInterface::class);
        $session->set('token', 'secret');
        $oldId = $session->id();

        $session->invalidate();

        self::assertFalse($session->isStarted());
        $session->start();
        self::assertNotSame($oldId, $session->id());
        self::assertNull($session->get('token'));
    }

    public function testFlashValuesSurviveOnlyTheNextRequest(): void
    {
        $app = new Application($this->basePath);

        /** @var EncryptedSession $session */
        $session = $app->make(SessionInterface::class);
        $session->flash('status', 'saved');
        self::assertSame('saved', $session->get('status'));

        $session->close();
        $session->start();

        self::assertSame('saved', $session->get('status'));

        $session->close();
        $session->start();

        self::assertNull($session->get('status'));
    }

    public function testNowValuesAreClearedAfterTheCurrentRequest(): void
    {
        $app = new Application($this->basePath);

        /** @var EncryptedSession $session */
        $session = $app->make(SessionInterface::class);
        $session->now('banner', 'one-request');
        self::assertSame('one-request', $session->get('banner'));

        $session->close();
        $session->start();

        self::assertNull($session->get('banner'));
    }

    public function testReflashAndKeepExtendFlashLifetime(): void
    {
        $app = new Application($this->basePath);

        /** @var EncryptedSession $session */
        $session = $app->make(SessionInterface::class);
        $session->flash('notice', 'persist');
        $session->flash('warning', 'keep-one');

        $session->close();
        $session->start();

        self::assertSame('persist', $session->get('notice'));
        self::assertSame('keep-one', $session->get('warning'));

        $session->keep(['warning']);
        $session->reflash();
        $session->close();
        $session->start();

        self::assertSame('persist', $session->get('notice'));
        self::assertSame('keep-one', $session->get('warning'));

        $session->close();
        $session->start();

        self::assertNull($session->get('notice'));
        self::assertNull($session->get('warning'));
    }
}
