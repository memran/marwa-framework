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
            $this->basePath . '/resources/views/themes/dark/views/welcome.twig',
            $this->basePath . '/resources/views/themes/dark/manifest.php',
            $this->basePath . '/resources/views/themes/default/views/welcome.twig',
            $this->basePath . '/resources/views/themes/default/manifest.php',
            $this->basePath . '/resources/views/welcome.twig',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

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

    public function testFallbackThemeIsUsedWhenRequestedThemeCannotBeActivated(): void
    {
        $app = new Application($this->basePath);
        $app->view()->setFallbackTheme('default');
        $app->view()->theme('missing-theme');

        self::assertSame('default', $app->view()->theme());
        self::assertStringContainsString('Default Alice framework', $app->view()->render('welcome', ['name' => 'Alice']));
    }
}
