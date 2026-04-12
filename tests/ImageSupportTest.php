<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Supports\Image;
use PHPUnit\Framework\TestCase;

final class ImageSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        if (!extension_loaded('gd')) {
            self::markTestSkipped('GD extension is required for image tests.');
        }

        $this->basePath = sys_get_temp_dir() . '/marwa-image-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
    }

    protected function tearDown(): void
    {
        if (!isset($this->basePath)) {
            return;
        }

        foreach (glob($this->basePath . '/*') ?: [] as $file) {
            @unlink($file);
        }

        if (is_dir($this->basePath)) {
            @rmdir($this->basePath);
        }
    }

    public function testCanvasCanBeSavedAndLoadedFromDisk(): void
    {
        $path = $this->basePath . '/avatar.png';

        $image = Image::canvas(40, 20, '#336699');
        $savedPath = $image->save($path);

        self::assertSame($path, $savedPath);
        self::assertFileExists($path);

        $loaded = Image::fromFile($path);

        self::assertSame(40, $loaded->width());
        self::assertSame(20, $loaded->height());
        self::assertSame('image/png', $loaded->mime());
    }

    public function testResizeAndFitProduceExpectedDimensions(): void
    {
        $image = Image::canvas(200, 100, '#ffcc00');

        $resized = $image->copy()->resize(50, 50);
        self::assertSame(50, $resized->width());
        self::assertSame(25, $resized->height());

        $fitted = $image->copy()->fit(60, 60);
        self::assertSame(60, $fitted->width());
        self::assertSame(60, $fitted->height());
    }

    public function testHelperLoadsAnExistingImage(): void
    {
        $path = $this->basePath . '/helper.webp';
        Image::canvas(24, 24, '#22aa44')->save($path, 90);

        $image = image($path);

        self::assertSame(24, $image->width());
        self::assertSame(24, $image->height());
        self::assertSame('image/webp', $image->mime());
    }
}
