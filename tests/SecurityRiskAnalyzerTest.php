<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Security\RiskAnalyzer;
use Marwa\Framework\Supports\Config;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SecurityRiskAnalyzerTest extends TestCase
{
    private string $basePath;
    private string $logPath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-risk-' . bin2hex(random_bytes(6));
        $this->logPath = $this->basePath . '/storage/security/risk.jsonl';
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents(
            $this->basePath . '/config/security.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'risk' => [
        'enabled' => true,
        'logPath' => '{$this->logPath}',
        'pruneAfterDays' => 30,
        'topCount' => 5,
    ],
];
PHP
        );
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/security.php',
            $this->basePath . '/.env',
            $this->logPath,
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/storage/security');
        @rmdir($this->basePath . '/storage');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
    }

    public function testRiskAnalyzerRecordsReportsAndPrunesSignals(): void
    {
        $app = new Application($this->basePath);
        $analyzer = new RiskAnalyzer($app, new Config($this->basePath . '/config'), new NullLogger());

        $analyzer->record('csrf', 'Rejected request with an invalid CSRF token.', ['path' => '/account'], 90);
        $analyzer->record('throttle', 'Rejected request after exceeding the throttling limit.', ['path' => '/api'], 60);

        $report = $analyzer->report();

        self::assertSame(2, $report['total']);
        self::assertSame(1, $report['byCategory']['csrf']);
        self::assertSame(1, $report['byCategory']['throttle']);
        self::assertSame(1, $report['byScore']['high']);
        self::assertSame(1, $report['byScore']['medium']);
        self::assertCount(2, $report['latest']);
        self::assertFileExists($this->logPath);

        file_put_contents(
            $this->logPath,
            json_encode([
                'timestamp' => gmdate(DATE_ATOM, time() - 172800),
                'category' => 'host',
                'message' => 'Rejected request with an untrusted host.',
                'score' => 80,
                'context' => ['path' => '/admin'],
            ], JSON_THROW_ON_ERROR) . PHP_EOL,
            FILE_APPEND
        );

        self::assertSame(1, $analyzer->prune(1));
        self::assertStringContainsString('csrf', (string) file_get_contents($this->logPath));
    }
}
