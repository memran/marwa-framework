<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ViewSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-view-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/resources/views/themes/default/views', 0777, true);
        mkdir($this->basePath . '/resources/views/themes/dark/views', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_DEBUG=false\nTIMEZONE=UTC\n");
        file_put_contents(
            $this->basePath . '/resources/views/welcome.twig',
            'Base {{ name }} {{ appName|default("framework") }}'
        );
        file_put_contents(
            $this->basePath . '/resources/views/themes/default/manifest.php',
            <<<PHP
<?php

return [
    'name' => 'default',
    'assets_url' => '/themes/default',
    'views_path' => 'views',
];
PHP
        );
        file_put_contents(
            $this->basePath . '/resources/views/themes/default/views/welcome.twig',
            'Default {{ name }} {{ appName|default("framework") }}'
        );
        file_put_contents(
            $this->basePath . '/resources/views/themes/dark/manifest.php',
            <<<PHP
<?php

return [
    'name' => 'dark',
    'parent' => 'default',
    'assets_url' => '/themes/dark',
    'views_path' => 'views',
];
PHP
        );
        file_put_contents(
            $this->basePath . '/resources/views/themes/dark/views/welcome.twig',
            'Dark {{ name }} {{ appName|default("framework") }}'
        );
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/config/view.php',
            $this->basePath . '/resources/views/themes/dark/views/welcome.twig',
            $this->basePath . '/resources/views/themes/dark/manifest.php',
            $this->basePath . '/resources/views/themes/default/views/welcome.twig',
            $this->basePath . '/resources/views/themes/default/manifest.php',
            $this->basePath . '/resources/views/welcome.twig',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        $this->deleteDirectory($this->basePath . '/storage');
        @rmdir($this->basePath . '/resources/views/themes/dark/views');
        @rmdir($this->basePath . '/resources/views/themes/dark');
        @rmdir($this->basePath . '/resources/views/themes/default/views');
        @rmdir($this->basePath . '/resources/views/themes/default');
        @rmdir($this->basePath . '/resources/views/themes');
        @rmdir($this->basePath . '/resources/views');
        @rmdir($this->basePath . '/resources');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['APP_DEBUG'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['APP_DEBUG'], $_SERVER['TIMEZONE']);
    }

    public function testViewHelperReturnsResponseAndServiceSupportsThemeSwitching(): void
    {
        $app = new Application($this->basePath);

        $app->view()->share('appName', 'Marwa');

        self::assertTrue($app->view()->exists('welcome'));
        self::assertSame('default', $app->view()->theme());

        $defaultResponse = view('welcome', ['name' => 'Alice']);
        self::assertInstanceOf(ResponseInterface::class, $defaultResponse);
        self::assertStringContainsString('Default Alice Marwa', (string) $defaultResponse->getBody());

        $app->view()->theme('dark');
        self::assertSame('dark', $app->view()->theme());

        $darkResponse = $app->view()->make('welcome', ['name' => 'Bob']);
        self::assertStringContainsString('Dark Bob Marwa', (string) $darkResponse->getBody());

        $rendered = $app->view()->render('welcome', ['name' => 'Carol']);
        self::assertSame('Dark Carol Marwa', trim($rendered));
    }

    public function testCompiledTwigCacheIsWrittenWhenCachingIsEnabled(): void
    {
        $this->writeViewConfig(true);

        $app = new Application($this->basePath);
        $app->view()->render('welcome', ['name' => 'Alice']);

        self::assertNotSame([], $this->cacheFiles());
    }

    public function testCompiledTwigCacheIsNotWrittenWhenCachingIsDisabled(): void
    {
        $this->writeViewConfig(false);

        $app = new Application($this->basePath);
        $app->view()->share('appName', 'Marwa');

        self::assertTrue($app->view()->exists('welcome'));
        self::assertSame('Default Alice Marwa', trim($app->view()->render('welcome', ['name' => 'Alice'])));
        self::assertSame([], $this->cacheFiles());
    }

    public function testFallbackThemeIsUsedWhenRequestedThemeCannotBeActivated(): void
    {
        $app = new Application($this->basePath);
        $app->view()->setFallbackTheme('default');
        $app->view()->theme('missing-theme');

        self::assertSame('default', $app->view()->theme());
        self::assertStringContainsString('Default Alice framework', $app->view()->render('welcome', ['name' => 'Alice']));
    }

    private function writeViewConfig(bool $cacheEnabled): void
    {
        $enabled = $this->boolToPhp($cacheEnabled);

        file_put_contents($this->basePath . '/config/view.php', <<<PHP
<?php

return [
    'cache' => [
        'enabled' => {$enabled},
    ],
];
PHP);
    }

    /**
     * @return list<string>
     */
    private function cacheFiles(): array
    {
        $cacheDir = $this->basePath . '/storage/cache/views';

        if (!is_dir($cacheDir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    private function boolToPhp(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($directory);
    }
}
