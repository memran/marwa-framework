<?php

declare(strict_types=1);

namespace Marwa\App\Images;

use Marwa\App\Contracts\ImageDriver;

/**
 * Class Image
 *
 * Public, fluent API with single-word, human-readable methods.
 * Wraps a Driver (GD by default) so you can swap implementations later.
 */
final class Image
{
    public function __construct(
        private ImageDriver $driver = new GDDriver()
    ) {}

    /** Load from file path. */
    public function open(string $path): self
    {
        $this->driver->open($path);
        return $this;
    }

    /** Load from binary string (e.g., HTTP upload). */
    public function read(string $binary): self
    {
        $this->driver->read($binary);
        return $this;
    }

    /** Get width in pixels. */
    public function width(): int
    {
        [$w] = $this->driver->size();
        return $w;
    }

    /** Get height in pixels. */
    public function height(): int
    {
        [, $h] = $this->driver->size();
        return $h;
    }

    /** Resize, preserving aspect if height omitted. */
    public function resize(int $width, ?int $height = null): self
    {
        $this->driver->resize($width, $height, true);
        return $this;
    }

    /** Resize to exact box with smart crop. */
    public function fit(int $width, int $height): self
    {
        $this->driver->fit($width, $height);
        return $this;
    }

    /** Crop to region. */
    public function crop(int $x, int $y, int $width, int $height): self
    {
        $this->driver->crop($x, $y, $width, $height);
        return $this;
    }

    /** Rotate by degrees (clockwise). */
    public function rotate(float $angle, int $bg = 0x00000000): self
    {
        $this->driver->rotate($angle, $bg);
        return $this;
    }

    /** Flip: 'h', 'v', or 'both'. */
    public function flip(string $mode = 'both'): self
    {
        $this->driver->flip($mode);
        return $this;
    }

    /** Place PNG watermark at (x,y) with opacity 0..100. */
    public function watermark(string $path, int $x, int $y, int $opacity = 70): self
    {
        $this->driver->watermark($path, $x, $y, $opacity);
        return $this;
    }

    /** Set output format: jpg|png|webp. */
    public function format(string $format): self
    {
        $this->driver->format($format);
        return $this;
    }

    /** Set output quality 0..100. */
    public function quality(int $quality): self
    {
        $this->driver->quality($quality);
        return $this;
    }

    /** Save to path. */
    public function save(string $path): void
    {
        $this->driver->save($path);
    }

    /** Return binary contents. */
    public function encode(): string
    {
        return $this->driver->encode();
    }
}
