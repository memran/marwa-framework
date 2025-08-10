<?php

namespace Marwa\App\Images;

use Marwa\App\Contracts\ImageDriver;
use GdImage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class GDDriver
 *
 * Fast, dependency-free GD implementation.
 */
final class GDDriver implements ImageDriver
{
    private const SUPPORTED = ['jpg', 'jpeg', 'png', 'webp'];
    private const FLIP_MODES = ['h', 'v', 'both'];

    private ?GdImage $im = null;
    private string $format = 'png';
    private int $quality = 82; // sane default

    public function __destruct()
    {
        if ($this->im instanceof GdImage) {
            imagedestroy($this->im);
        }
    }

    public function open(string $path): void
    {
        if ($path === '' || !is_file($path)) {
            throw new RuntimeException("Image not found: {$path}");
        }
        $data = file_get_contents($path);
        if ($data === false) {
            throw new RuntimeException("Failed to read image: {$path}");
        }
        $this->read($data);
        $this->format($this->detectFormatFromPath($path));
    }

    public function read(string $binary): void
    {
        $im = @imagecreatefromstring($binary);
        if (!$im instanceof GdImage) {
            throw new RuntimeException('Invalid image data.');
        }
        if ($this->im instanceof GdImage) {
            imagedestroy($this->im);
        }
        $this->im = $im;
        imagesavealpha($this->im, true);
        imagealphablending($this->im, true);
    }

    public function size(): array
    {
        $this->assertImage();
        return [imagesx($this->im), imagesy($this->im)];
    }

    public function resize(int $width, ?int $height = null, bool $preserveAspect = true): void
    {
        $this->assertImage();
        if ($width <= 0 || ($height !== null && $height <= 0)) {
            throw new InvalidArgumentException('Width/height must be positive.');
        }

        [$w, $h] = $this->size();
        if ($height === null && $preserveAspect) {
            $height = (int) round($h * ($width / $w));
        } elseif ($height === null) {
            $height = $h;
        }

        $dst = $this->blank($width, $height);
        imagecopyresampled($dst, $this->im, 0, 0, 0, 0, $width, $height, $w, $h);
        imagedestroy($this->im);
        $this->im = $dst;
    }

    public function fit(int $width, int $height): void
    {
        $this->assertImage();
        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Width/height must be positive.');
        }

        [$w, $h] = $this->size();
        $srcRatio = $w / $h;
        $dstRatio = $width / $height;

        if ($srcRatio > $dstRatio) {
            // wider than tall -> crop sides
            $newH = (int) round($w / $dstRatio);
            $y = (int) max(0, ($h - $newH) / 2);
            $this->crop(0, $y, $w, $newH);
        } else {
            // taller -> crop top/bottom
            $newW = (int) round($h * $dstRatio);
            $x = (int) max(0, ($w - $newW) / 2);
            $this->crop($x, 0, $newW, $h);
        }

        $this->resize($width, $height, false);
    }

    public function crop(int $x, int $y, int $width, int $height): void
    {
        $this->assertImage();
        [$w, $h] = $this->size();

        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Width/height must be positive.');
        }

        $x = max(0, min($x, $w - 1));
        $y = max(0, min($y, $h - 1));
        $width = min($width, $w - $x);
        $height = min($height, $h - $y);

        $dst = $this->blank($width, $height);
        imagecopy($dst, $this->im, 0, 0, $x, $y, $width, $height);
        imagedestroy($this->im);
        $this->im = $dst;
    }

    public function rotate(float $angle, int $bg = 0x00000000): void
    {
        $this->assertImage();
        $bgColor = imagecolorallocatealpha(
            $this->im,
            ($bg >> 16) & 0xFF,
            ($bg >> 8) & 0xFF,
            $bg & 0xFF,
            127 - (($bg >> 24) & 0x7F) // preserve alpha byte if provided
        );
        $rot = imagerotate($this->im, -$angle, $bgColor); // GD rotates counter-clockwise
        if (!$rot instanceof GdImage) {
            throw new RuntimeException('Rotate failed.');
        }
        imagesavealpha($rot, true);
        imagedestroy($this->im);
        $this->im = $rot;
    }

    public function flip(string $mode): void
    {
        $this->assertImage();
        $mode = strtolower($mode);
        if (!in_array($mode, self::FLIP_MODES, true)) {
            throw new InvalidArgumentException("Flip mode must be 'h', 'v', or 'both'.");
        }

        [$w, $h] = $this->size();
        $dst = $this->blank($w, $h);

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $sx = ($mode === 'h' || $mode === 'both') ? $w - 1 - $x : $x;
                $sy = ($mode === 'v' || $mode === 'both') ? $h - 1 - $y : $y;
                imagesetpixel($dst, $x, $y, imagecolorat($this->im, $sx, $sy));
            }
        }

        imagedestroy($this->im);
        $this->im = $dst;
    }

    public function watermark(string $path, int $x, int $y, int $opacity): void
    {
        $this->assertImage();
        if (!is_file($path)) {
            throw new RuntimeException("Watermark not found: {$path}");
        }
        $wm = @imagecreatefrompng($path);
        if (!$wm instanceof GdImage) {
            throw new RuntimeException('Watermark must be a PNG with alpha.');
        }

        [$ww, $wh] = [imagesx($wm), imagesy($wm)];
        $opacity = max(0, min(100, $opacity));
        imagecopymerge($this->im, $wm, $x, $y, 0, 0, $ww, $wh, $opacity);
        imagedestroy($wm);
    }

    public function format(string $format): void
    {
        $format = strtolower($format);
        if ($format === 'jpeg') {
            $format = 'jpg';
        }
        if (!in_array($format, self::SUPPORTED, true)) {
            throw new InvalidArgumentException('Unsupported format. Use jpg, png, or webp.');
        }
        $this->format = $format;
    }

    public function quality(int $quality): void
    {
        $this->quality = max(0, min(100, $quality));
    }

    public function save(string $path): void
    {
        $this->assertImage();
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create directory: {$dir}");
        }
        $this->write($path);
    }

    public function encode(): string
    {
        $this->assertImage();
        ob_start();
        $this->write(null);
        $data = (string) ob_get_clean();
        if ($data === '') {
            throw new RuntimeException('Encode failed.');
        }
        return $data;
    }

    // ---- internals ---------------------------------------------------------

    private function assertImage(): void
    {
        if (!$this->im instanceof GdImage) {
            throw new RuntimeException('No image loaded.');
        }
    }

    private function blank(int $width, int $height): GdImage
    {
        $dst = imagecreatetruecolor($width, $height);
        if (!$dst instanceof GdImage) {
            throw new RuntimeException('Allocation failed.');
        }
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
        return $dst;
    }

    private function detectFormatFromPath(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, self::SUPPORTED, true) ? ($ext === 'jpeg' ? 'jpg' : $ext) : 'png';
    }

    private function write(?string $path): void
    {
        switch ($this->format) {
            case 'jpg':
                // JPEG quality: 0..100
                if ($path) {
                    imagejpeg($this->im, $path, $this->quality);
                } else {
                    imagejpeg($this->im, null, $this->quality);
                }
                break;
            case 'png':
                // PNG compression: 0 (no) .. 9 (max). Map 0..100 to 9..0 (inverse).
                $level = (int) round((100 - $this->quality) * 9 / 100);
                $level = max(0, min(9, $level));
                if ($path) {
                    imagepng($this->im, $path, $level);
                } else {
                    imagepng($this->im, null, $level);
                }
                break;
            case 'webp':
                if (!function_exists('imagewebp')) {
                    throw new RuntimeException('WEBP not supported in this PHP build.');
                }
                if ($path) {
                    imagewebp($this->im, $path, $this->quality);
                } else {
                    imagewebp($this->im, null, $this->quality);
                }
                break;
        }
    }
}
