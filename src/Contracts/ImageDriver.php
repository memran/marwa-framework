<?php

namespace Marwa\App\Contracts;

/**
 * Interface Driver
 *
 * Minimal driver contract so we can swap GD/Imagick later without touching the API.
 */
interface ImageDriver
{
    public function open(string $path): void;
    public function read(string $binary): void;
    public function size(): array;               // [int $width, int $height]
    public function resize(int $width, ?int $height = null, bool $preserveAspect = true): void;
    public function fit(int $width, int $height): void;
    public function crop(int $x, int $y, int $width, int $height): void;
    public function rotate(float $angle, int $bg = 0x00000000): void;
    public function flip(string $mode): void;    // 'h', 'v', 'both'
    public function watermark(string $path, int $x, int $y, int $opacity): void;
    public function format(string $format): void; // 'jpg','png','webp'
    public function quality(int $quality): void; // 0..100 (mapped per format)
    public function save(string $path): void;
    public function encode(): string;
}
