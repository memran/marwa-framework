<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Supports\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private string $configDir;

    protected function setUp(): void
    {
        $this->configDir = sys_get_temp_dir() . '/marwa-config-' . bin2hex(random_bytes(6));
        mkdir($this->configDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->configDir . '/*.php') ?: [] as $file) {
            unlink($file);
        }

        @rmdir($this->configDir);
    }

    public function testItLoadsConfigByDotNotation(): void
    {
        file_put_contents(
            $this->configDir . '/app.php',
            <<<'PHP'
<?php

return [
    'name' => 'Marwa',
    'features' => [
        'debug' => true,
    ],
];
PHP
        );

        $config = new Config($this->configDir);
        $config->load('app.php');

        self::assertSame('Marwa', $config->getString('app.name'));
        self::assertTrue($config->getBool('app.features.debug'));
    }

    public function testLoadIfExistsSkipsMissingOrDuplicateFiles(): void
    {
        file_put_contents(
            $this->configDir . '/event.php',
            <<<'PHP'
<?php

return ['listeners' => []];
PHP
        );

        $config = new Config($this->configDir);

        self::assertFalse($config->loadIfExists('missing.php'));
        self::assertTrue($config->loadIfExists('event.php'));
        self::assertFalse($config->loadIfExists('event.php'));
        self::assertTrue($config->isLoaded('event'));
    }

    public function testHasRecognizesNullValuesAsPresent(): void
    {
        file_put_contents(
            $this->configDir . '/app.php',
            <<<'PHP'
<?php

return [
    'nullable' => null,
];
PHP
        );

        $config = new Config($this->configDir);
        $config->load('app.php');

        self::assertTrue($config->has('app.nullable'));
        self::assertNull($config->get('app.nullable'));
    }
}
