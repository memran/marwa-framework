<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Bootstrappers\ModuleBootstrapCache;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Tests\Fixtures\Modules\Blog\BlogModuleServiceProvider;
use Marwa\Module\Module;
use PHPUnit\Framework\TestCase;

final class ModuleBootstrapCacheTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        Runtime::setConsoleOverride(false);
        $this->basePath = sys_get_temp_dir() . '/marwa-module-bootstrap-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/modules/Cached', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        $this->writeModuleConfig();
        $this->writeModuleManifest('Cached', 'cached', 'first', null, true);
        $GLOBALS['marwa_module_manifest_reads'] = 0;
        BlogModuleServiceProvider::$registerCalls = 0;
        BlogModuleServiceProvider::$bootCalls = 0;
    }

    protected function tearDown(): void
    {
        Runtime::setConsoleOverride(null);

        foreach ([
            $this->basePath . '/modules/Extra/manifest.php',
            $this->basePath . '/modules/Cached/manifest.php',
            $this->basePath . '/storage/cache/module-bootstrap.php',
            $this->basePath . '/storage/cache/modules.php',
            $this->basePath . '/config/module.php',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/storage/cache');
        @rmdir($this->basePath . '/storage');
        @rmdir($this->basePath . '/modules/Extra');
        @rmdir($this->basePath . '/modules/Cached');
        @rmdir($this->basePath . '/modules');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $GLOBALS['marwa_module_manifest_reads'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testSubsequentBootUsesCompiledCacheWithoutReadingManifests(): void
    {
        $first = $this->bootApp();

        self::assertSame(1, $GLOBALS['marwa_module_manifest_reads']);
        self::assertFileExists($this->cacheFile());
        self::assertTrue($first->hasModule('cached'));

        BlogModuleServiceProvider::$registerCalls = 0;
        BlogModuleServiceProvider::$bootCalls = 0;

        $second = $this->bootApp();

        self::assertSame(1, $GLOBALS['marwa_module_manifest_reads'], 'A fresh compiled cache must skip the module scan.');
        self::assertTrue($second->hasModule('cached'));
        self::assertSame('Cached Module', $second->module('cached')->name());
        self::assertSame(1, BlogModuleServiceProvider::$registerCalls, 'Compiled providers must still register.');
        self::assertSame(1, BlogModuleServiceProvider::$bootCalls);
        self::assertTrue($second->has('module.blog.registered'));
    }

    public function testManifestChangeInvalidatesCompiledCache(): void
    {
        $this->bootApp();
        self::assertSame(1, $GLOBALS['marwa_module_manifest_reads']);

        $this->writeModuleManifest('Cached', 'cached', 'second', time() + 5);

        $app = $this->bootApp();

        self::assertSame(2, $GLOBALS['marwa_module_manifest_reads'], 'A manifest change must force a rescan.');
        self::assertSame('second', $app->module('cached')->get('marker'));
    }

    public function testNewModuleDirectoryInvalidatesCompiledCache(): void
    {
        $this->bootApp();
        self::assertSame(1, $GLOBALS['marwa_module_manifest_reads']);

        mkdir($this->basePath . '/modules/Extra', 0777, true);
        $this->writeModuleManifest('Extra', 'extra', 'extra');

        $app = $this->bootApp();

        self::assertSame(3, $GLOBALS['marwa_module_manifest_reads'], 'A new module must force a rescan.');
        self::assertTrue($app->hasModule('cached'));
        self::assertTrue($app->hasModule('extra'));
    }

    public function testCorruptedCacheFallsBackToFilesystemScan(): void
    {
        mkdir($this->basePath . '/storage/cache', 0777, true);
        file_put_contents($this->cacheFile(), "<?php return 'not-a-payload';");

        $app = $this->bootApp();

        self::assertSame(1, $GLOBALS['marwa_module_manifest_reads']);
        self::assertTrue($app->hasModule('cached'));
    }

    public function testCompiledCacheCanBeDisabled(): void
    {
        $this->writeModuleConfig(['bootstrapCache' => null]);

        $this->bootApp();

        self::assertFileDoesNotExist($this->cacheFile());
    }

    public function testTrustedCacheIgnoresManifestTimestamps(): void
    {
        $modulePath = $this->basePath . '/modules/Cached';
        $modulePaths = [$this->basePath . '/modules'];
        $cacheFile = $this->cacheFile();

        $cache = new ModuleBootstrapCache($cacheFile, $modulePaths);
        $cache->save($cache->compile([
            'cached' => new Module('cached', $modulePath, [
                'name' => 'Cached Module',
                'slug' => 'cached',
                'providers' => [],
            ]),
        ]));

        self::assertNotNull($cache->load());

        touch($modulePath . '/manifest.php', time() + 5);

        self::assertNull($cache->load(), 'A changed manifest must invalidate the untrusted cache.');
        self::assertNotNull(
            (new ModuleBootstrapCache($cacheFile, $modulePaths, false, true))->load(),
            'A trusted cache must load without freshness checks.'
        );
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

    private function cacheFile(): string
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
            'cache' => null,
            'bootstrapCache' => $this->basePath . '/storage/cache/module-bootstrap.php',
        ], $overrides);

        file_put_contents(
            $this->basePath . '/config/module.php',
            "<?php\n\nreturn " . var_export($config, true) . ";\n"
        );
    }

    private function writeModuleManifest(string $directory, string $slug, string $marker, ?int $mtime = null, bool $withProvider = false): void
    {
        $manifest = [
            'name' => ucfirst($slug) . ' Module',
            'slug' => $slug,
            'marker' => $marker,
            'providers' => $withProvider ? [BlogModuleServiceProvider::class] : [],
        ];

        $path = $this->basePath . '/modules/' . $directory . '/manifest.php';

        file_put_contents($path, "<?php\n\n\$GLOBALS['marwa_module_manifest_reads'] = (\$GLOBALS['marwa_module_manifest_reads'] ?? 0) + 1;\n\nreturn " . var_export($manifest, true) . ";\n");

        if ($mtime !== null) {
            touch($path, $mtime);
        }
    }
}
