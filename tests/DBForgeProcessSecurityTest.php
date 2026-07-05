<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Config\Config;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Console\Commands\DbDropCommand;
use Marwa\Framework\Database\DBForge;
use PHPUnit\Framework\TestCase;

final class DBForgeProcessSecurityTest extends TestCase
{
    private string $basePath;
    private string $binPath;
    private string $logPath;
    private string|false $originalPath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-db-process-' . bin2hex(random_bytes(6));
        $this->binPath = $this->basePath . '/bin';
        $this->logPath = $this->basePath . '/process.json';
        $this->originalPath = getenv('PATH');

        mkdir($this->binPath, 0777, true);
        putenv('PATH=' . $this->binPath . PATH_SEPARATOR . ($this->originalPath === false ? '' : $this->originalPath));
        putenv('MARWA_DB_PROCESS_LOG=' . $this->logPath);
    }

    protected function tearDown(): void
    {
        putenv('MARWA_DB_PROCESS_LOG');
        putenv($this->originalPath === false ? 'PATH' : 'PATH=' . $this->originalPath);

        $this->removeDirectory($this->basePath);
    }

    public function testMysqlBackupUsesEnvironmentPasswordWithoutPasswordArgument(): void
    {
        $this->writeRecorder('mysqldump', 'MYSQL_PWD');

        $forge = $this->forge('mysql', [
            'host' => 'db.local',
            'port' => 3307,
            'username' => 'backup_user',
            'password' => 'super-secret',
            'database' => 'app_db',
        ]);

        self::assertTrue($forge->backup($this->basePath . '/backup.sql'));

        $record = $this->readProcessRecord();

        self::assertSame('super-secret', $record['password']);
        self::assertContains('--host=db.local', $record['argv']);
        self::assertContains('--port=3307', $record['argv']);
        self::assertContains('--user=backup_user', $record['argv']);
        self::assertContains('app_db', $record['argv']);
        self::assertNotContains('--password=super-secret', $record['argv']);
        self::assertStringNotContainsString('super-secret', implode(' ', $record['argv']));
    }

    public function testPgsqlRestoreUsesConfiguredPasswordEnvironment(): void
    {
        $this->writeRecorder('psql', 'PGPASSWORD');
        $backupPath = $this->basePath . '/backup.sql';
        file_put_contents($backupPath, '-- backup');

        $forge = $this->forge('pgsql', [
            'host' => 'pg.local',
            'port' => 5433,
            'username' => 'restore_user',
            'password' => 'pg-secret',
            'database' => 'app_pg',
        ]);

        self::assertTrue($forge->restore($backupPath));

        $record = $this->readProcessRecord();

        self::assertSame('pg-secret', $record['password']);
        self::assertContains('--host=pg.local', $record['argv']);
        self::assertContains('--port=5433', $record['argv']);
        self::assertContains('--user=restore_user', $record['argv']);
        self::assertContains('--dbname=app_pg', $record['argv']);
        self::assertContains('--file=' . $backupPath, $record['argv']);
        self::assertStringNotContainsString('pg-secret', implode(' ', $record['argv']));
    }

    public function testDbDropForceDoesNotAcceptAValue(): void
    {
        $option = (new DbDropCommand())->getDefinition()->getOption('force');

        self::assertFalse($option->acceptValue());
        self::assertFalse($option->isValueOptional());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function forge(string $driver, array $config): DBForge
    {
        $manager = new ConnectionManager(new Config([
            'default' => ['driver' => $driver],
        ]));

        return new DBForge($manager, $config);
    }

    private function writeRecorder(string $name, string $passwordEnv): void
    {
        $path = $this->binPath . '/' . $name;
        $script = <<<PHP
#!/usr/bin/env php
<?php
file_put_contents((string) getenv('MARWA_DB_PROCESS_LOG'), json_encode([
    'argv' => array_slice(\$argv, 1),
    'password' => getenv('{$passwordEnv}') ?: null,
], JSON_THROW_ON_ERROR));
PHP;

        file_put_contents($path, $script);
        chmod($path, 0755);
    }

    /**
     * @return array{argv:list<string>,password:?string}
     */
    private function readProcessRecord(): array
    {
        self::assertFileExists($this->logPath);

        $record = json_decode((string) file_get_contents($this->logPath), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($record);
        self::assertIsArray($record['argv'] ?? null);

        return $record;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
