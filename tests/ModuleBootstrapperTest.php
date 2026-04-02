<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Adapters\ViewAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Tests\Fixtures\Modules\Blog\BlogModuleServiceProvider;
use Marwa\Module\ModuleBuilder;
use PHPUnit\Framework\TestCase;

final class ModuleBootstrapperTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-module-runtime-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/resources/views/themes/default', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");

        $moduleFixture = __DIR__ . '/Fixtures/Modules';
        file_put_contents(
            $this->basePath . '/config/module.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'paths' => ['{$moduleFixture}'],
    'cache' => '{$this->basePath}/bootstrap/cache/modules.php',
];
PHP
        );

        BlogModuleServiceProvider::$registerCalls = 0;
        BlogModuleServiceProvider::$bootCalls = 0;
        unset($GLOBALS['marwa_module_routes']);
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/config/module.php');
        @unlink($this->basePath . '/.env');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/resources/views/themes/default');
        @rmdir($this->basePath . '/resources/views/themes');
        @rmdir($this->basePath . '/resources/views');
        @rmdir($this->basePath . '/resources');
        @unlink($this->basePath . '/bootstrap/cache/modules.php');
        @rmdir($this->basePath . '/bootstrap/cache');
        @rmdir($this->basePath . '/bootstrap');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $GLOBALS['marwa_module_routes'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
        restore_error_handler();
        restore_exception_handler();
        BlogModuleServiceProvider::$registerCalls = 0;
        BlogModuleServiceProvider::$bootCalls = 0;
    }

    public function testModuleBootstrapperRegistersProvidersRoutesAndViews(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        self::assertSame(1, BlogModuleServiceProvider::$registerCalls);
        self::assertSame(1, BlogModuleServiceProvider::$bootCalls);
        self::assertTrue($app->has('module.blog.registered'));
        self::assertTrue($app->has('module.blog.booted'));
        self::assertSame(['blog-http'], $GLOBALS['marwa_module_routes']);
        self::assertTrue($app->has(ModuleBuilder::class));
        self::assertTrue($app->hasModule('blog'));
        self::assertSame('Blog Module', $app->module('blog')->name());
        self::assertArrayHasKey('blog', $app->modules());

        $view = $app->make(ViewAdapter::class)->getView();
        self::assertSame('Hello from module', trim($view->render('@blog/hello.twig')));
    }

    public function testModuleRoutesAreSkippedWhenCompiledRouteCacheExists(): void
    {
        mkdir($this->basePath . '/bootstrap/cache', 0777, true);
        file_put_contents($this->basePath . '/bootstrap/cache/routes.php', '<?php return static function (): void {};');

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        self::assertArrayNotHasKey('marwa_module_routes', $GLOBALS);
    }
}
