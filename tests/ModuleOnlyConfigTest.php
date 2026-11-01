<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use FilesystemIterator;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Exceptions\ModuleDependencyException;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Tests\Fixtures\Modules\Blog\BlogModuleServiceProvider;
use Marwa\Module\Exception\ModuleConfigurationException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ModuleOnlyConfigTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        Runtime::setConsoleOverride(false);
        $this->basePath = sys_get_temp_dir() . '/marwa-module-only-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/modules', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        BlogModuleServiceProvider::$registerCalls = 0;
        BlogModuleServiceProvider::$bootCalls = 0;
    }

    protected function tearDown(): void
    {
        Runtime::setConsoleOverride(null);
        $this->removeDirectory($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );

        BlogModuleServiceProvider::$registerCalls = 0;
        BlogModuleServiceProvider::$bootCalls = 0;
    }

    public function testOnlyLoadsSelectedModulesAndSkipsTheRest(): void
    {
        $this->writeManifest('Auth', 'auth', [\Marwa\Framework\Tests\Fixtures\Modules\Auth\AuthModuleServiceProvider::class], ['user']);
        $this->writeManifest('User', 'user', [\Marwa\Framework\Tests\Fixtures\Modules\User\UserModuleServiceProvider::class]);
        $this->writeManifest('Blog', 'blog', [BlogModuleServiceProvider::class]);
        $this->writeModuleConfig(['only' => ['blog']]);

        $app = $this->bootApp();

        self::assertTrue($app->hasModule('blog'));
        self::assertFalse($app->hasModule('auth'));
        self::assertFalse($app->hasModule('user'));
        self::assertSame(1, BlogModuleServiceProvider::$registerCalls);
        self::assertSame(1, BlogModuleServiceProvider::$bootCalls);
        self::assertTrue($app->has('module.blog.booted'));
        self::assertFalse($app->has('module.auth.registered'));
        self::assertFalse($app->has('module.user.registered'));
    }

    public function testOnlyLoadsTransitiveDependencies(): void
    {
        $this->writeManifest('Auth', 'auth', [\Marwa\Framework\Tests\Fixtures\Modules\Auth\AuthModuleServiceProvider::class], ['user']);
        $this->writeManifest('User', 'user', [\Marwa\Framework\Tests\Fixtures\Modules\User\UserModuleServiceProvider::class]);
        $this->writeManifest('Blog', 'blog', [BlogModuleServiceProvider::class]);
        $this->writeModuleConfig(['only' => ['auth']]);

        $app = $this->bootApp();

        self::assertTrue($app->hasModule('auth'));
        self::assertTrue($app->hasModule('user'));
        self::assertFalse($app->hasModule('blog'));
        self::assertTrue($app->has('module.auth.booted'));
        self::assertTrue($app->has('module.user.booted'));
        self::assertFalse($app->has('module.blog.registered'));
        self::assertSame(0, BlogModuleServiceProvider::$registerCalls);
        self::assertSame(0, BlogModuleServiceProvider::$bootCalls);
    }

    public function testOnlyResolvesDependenciesCaseInsensitively(): void
    {
        $this->writeManifest('Auth', 'AUTH', [\Marwa\Framework\Tests\Fixtures\Modules\Auth\AuthModuleServiceProvider::class], ['User']);
        $this->writeManifest('User', 'User', [\Marwa\Framework\Tests\Fixtures\Modules\User\UserModuleServiceProvider::class]);
        $this->writeModuleConfig(['only' => ['Auth']]);

        $app = $this->bootApp();

        self::assertTrue($app->hasModule('AUTH'));
        self::assertTrue($app->hasModule('User'));
        self::assertTrue($app->has('module.auth.booted'));
        self::assertTrue($app->has('module.user.booted'));
    }

    public function testOnlyRejectsUnknownModuleSlugs(): void
    {
        $this->writeManifest('Blog', 'blog', [BlogModuleServiceProvider::class]);
        $this->writeModuleConfig(['only' => ['missing']]);

        $app = new Application($this->basePath);

        try {
            $app->make(AppBootstrapper::class)->bootstrap();
            self::fail('An unknown module in [module.only] must fail the bootstrap.');
        } catch (ModuleConfigurationException $exception) {
            self::assertSame(
                'Module [missing] configured in [module.only] was not found.',
                $exception->getMessage()
            );
        } finally {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testOnlyRejectsMissingDependencies(): void
    {
        $this->writeManifest('Auth', 'auth', [\Marwa\Framework\Tests\Fixtures\Modules\Auth\AuthModuleServiceProvider::class], ['user']);
        $this->writeModuleConfig(['only' => ['auth']]);

        $app = new Application($this->basePath);

        try {
            $app->make(AppBootstrapper::class)->bootstrap();
            self::fail('A missing dependency must fail the bootstrap.');
        } catch (ModuleDependencyException $exception) {
            self::assertSame(
                'Module [auth] requires missing module(s): user.',
                $exception->getMessage()
            );
        } finally {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    public function testOnlyKeepsEveryDiscoveredModuleInTheBootstrapCache(): void
    {
        $this->writeManifest('Auth', 'auth', [\Marwa\Framework\Tests\Fixtures\Modules\Auth\AuthModuleServiceProvider::class], ['user']);
        $this->writeManifest('User', 'user', [\Marwa\Framework\Tests\Fixtures\Modules\User\UserModuleServiceProvider::class]);
        $this->writeManifest('Blog', 'blog', [BlogModuleServiceProvider::class]);
        $this->writeModuleConfig(['only' => ['blog']]);

        $first = $this->bootApp();

        self::assertTrue($first->hasModule('blog'));
        self::assertFileExists($this->bootstrapCacheFile());

        /** @var array{modules: array<string, mixed>} $payload */
        $payload = include $this->bootstrapCacheFile();
        self::assertSame(['auth', 'blog', 'user'], array_keys($payload['modules']));

        $cacheHash = hash_file('sha256', $this->bootstrapCacheFile());
        self::assertIsString($cacheHash);

        $this->writeModuleConfig(['only' => ['auth']]);
        $second = $this->bootApp();

        self::assertTrue($second->hasModule('auth'));
        self::assertTrue($second->hasModule('user'));
        self::assertFalse($second->hasModule('blog'));
        self::assertSame($cacheHash, hash_file('sha256', $this->bootstrapCacheFile()), 'The compiled cache must be reused when only the allowlist changes.');
    }

    public function testEmptyOnlyKeepsLoadingEveryModule(): void
    {
        $this->writeManifest('Auth', 'auth', [\Marwa\Framework\Tests\Fixtures\Modules\Auth\AuthModuleServiceProvider::class], ['user']);
        $this->writeManifest('User', 'user', [\Marwa\Framework\Tests\Fixtures\Modules\User\UserModuleServiceProvider::class]);
        $this->writeManifest('Blog', 'blog', [BlogModuleServiceProvider::class]);
        $this->writeModuleConfig(['only' => []]);

        $app = $this->bootApp();

        self::assertTrue($app->hasModule('auth'));
        self::assertTrue($app->hasModule('user'));
        self::assertTrue($app->hasModule('blog'));
    }

    private function bootApp(): Application
    {
        unset($GLOBALS['marwa_app']);

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        // Each Application installs its own error/exception handlers; drop them
        // so PHPUnit's handler balance stays intact across multiple boots.
        restore_error_handler();
        restore_exception_handler();

        return $app;
    }

    private function bootstrapCacheFile(): string
    {
        return $this->basePath . '/storage/cache/module-bootstrap.php';
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function writeModuleConfig(array $overrides = []): void
    {
        $config = array_replace([
            'enabled' => true,
            'paths' => [$this->basePath . '/modules'],
            'cache' => $this->basePath . '/storage/cache/modules.php',
            'bootstrapCache' => $this->bootstrapCacheFile(),
        ], $overrides);

        file_put_contents(
            $this->basePath . '/config/module.php',
            "<?php\n\nreturn " . var_export($config, true) . ";\n"
        );
    }

    /**
     * @param list<class-string> $providers
     * @param list<string> $requires
     */
    private function writeManifest(string $directory, string $slug, array $providers = [], array $requires = []): void
    {
        $moduleDir = $this->basePath . '/modules/' . $directory;

        if (!is_dir($moduleDir)) {
            mkdir($moduleDir, 0777, true);
        }

        file_put_contents(
            $moduleDir . '/manifest.php',
            "<?php\n\nreturn " . var_export([
                'name' => ucfirst($slug) . ' Module',
                'slug' => $slug,
                'providers' => $providers,
                'requires' => $requires,
            ], true) . ";\n"
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
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
