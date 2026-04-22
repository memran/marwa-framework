<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SecurityReportCommandTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-security-report-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/security.php',
            $this->basePath . '/.env',
            $this->basePath . '/storage/security/risk.jsonl',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/storage/security');
        @rmdir($this->basePath . '/storage');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);

        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testSecurityReportCommandSummarizesAndPrunesSignals(): void
    {
        $riskLog = $this->basePath . '/storage/security/risk.jsonl';
        mkdir(dirname($riskLog), 0777, true);
        file_put_contents(
            $this->basePath . '/config/security.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'risk' => [
        'enabled' => true,
        'logPath' => '{$riskLog}',
        'pruneAfterDays' => 30,
        'topCount' => 5,
    ],
];
PHP
        );

        file_put_contents(
            $riskLog,
            json_encode([
                'timestamp' => gmdate(DATE_ATOM, time() - 172800),
                'category' => 'csrf',
                'message' => 'Rejected request with an invalid CSRF token.',
                'score' => 80,
                'context' => ['path' => '/account'],
            ], JSON_THROW_ON_ERROR) . PHP_EOL .
            json_encode([
                'timestamp' => gmdate(DATE_ATOM),
                'category' => 'throttle',
                'message' => 'Rejected request after exceeding the throttling limit.',
                'score' => 60,
                'context' => ['path' => '/api'],
            ], JSON_THROW_ON_ERROR) . PHP_EOL
        );

        $app = new Application($this->basePath);
        $console = $app->console()->application();
        $this->handlersBooted = true;

        $tester = new CommandTester($console->find('security:report'));
        self::assertSame(0, $tester->execute([
            '--since-hours' => '72',
            '--prune-days' => '1',
        ]));

        $display = $tester->getDisplay();
        self::assertStringContainsString('Total signals: 2', $display);
        self::assertStringContainsString('csrf: 1', $display);
        self::assertStringContainsString('throttle: 1', $display);
        self::assertStringContainsString('Pruned 1 old signals.', $display);
    }
}
