<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

use GdImage;

final class Image
{
    private const SUPPORTED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private ?string $path = null;
    private string $mime = 'image/png';

    public function __construct(private GdImage $resource) {}

    public static function fromFile(string $path): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('Image file [%s] is not readable.', $path));
        }

        $contents = file_get_contents($path);

        if (!is_string($contents) || $contents === '') {
            throw new \RuntimeException(sprintf('Image file [%s] could not be read.', $path));
        }

        $resource = imagecreatefromstring($contents);

        if (!$resource instanceof GdImage) {
            throw new \RuntimeException(sprintf('Image file [%s] could not be decoded.', $path));
        }

        $image = new self($resource);
        $image->path = $path;
        $image->mime = self::detectMimeType($path);
        imagealphablending($image->resource, true);
        imagesavealpha($image->resource, true);

        return $image;
    }

    public static function canvas(int $width, int $height, string $background = '#ffffff'): self
    {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException('Canvas dimensions must be greater than zero.');
        }

        $resource = imagecreatetruecolor($width, $height);

        if (!$resource instanceof GdImage) {
            throw new \RuntimeException('Unable to create image canvas.');
        }

        imagealphablending($resource, false);
        imagesavealpha($resource, true);
        $color = self::allocateColor($resource, $background);
        imagefill($resource, 0, 0, $color);

        return new self($resource);
    }

    public function width(): int
    {
        return imagesx($this->resource);
    }

    public function height(): int
    {
        return imagesy($this->resource);
    }

    public function mime(): string
    {
        return $this->mime;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function resize(int $maxWidth, int $maxHeight, bool $upscale = false): self
    {
        if ($maxWidth <= 0 || $maxHeight <= 0) {
            throw new \InvalidArgumentException('Resize dimensions must be greater than zero.');
        }

        $sourceWidth = $this->width();
        $sourceHeight = $this->height();
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);

        if (!$upscale) {
            $ratio = min($ratio, 1.0);
        }

        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));

        return $this->resample($targetWidth, $targetHeight, 0, 0, $sourceWidth, $sourceHeight);
    }

    public function crop(int $width, int $height, int $x = 0, int $y = 0): self
    {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException('Crop dimensions must be greater than zero.');
        }

        $crop = imagecrop($this->resource, [
            'x' => max(0, $x),
            'y' => max(0, $y),
            'width' => min($width, $this->width() - max(0, $x)),
            'height' => min($height, $this->height() - max(0, $y)),
        ]);

        if (!$crop instanceof GdImage) {
            throw new \RuntimeException('Unable to crop image.');
        }

        $this->destroyResource($this->resource);
        $this->resource = $crop;
        imagesavealpha($this->resource, true);

        return $this;
    }

    public function fit(int $width, int $height): self
    {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException('Fit dimensions must be greater than zero.');
        }

        $sourceWidth = $this->width();
        $sourceHeight = $this->height();
        $ratio = max($width / $sourceWidth, $height / $sourceHeight);
        $resizedWidth = max(1, (int) ceil($sourceWidth * $ratio));
        $resizedHeight = max(1, (int) ceil($sourceHeight * $ratio));

        $this->resample($resizedWidth, $resizedHeight, 0, 0, $sourceWidth, $sourceHeight);

        $x = max(0, (int) floor(($resizedWidth - $width) / 2));
        $y = max(0, (int) floor(($resizedHeight - $height) / 2));

        return $this->crop($width, $height, $x, $y);
    }

    public function save(?string $path = null, int $quality = 90): string
    {
        $targetPath = $path ?? $this->path;

        if ($targetPath === null || trim($targetPath) === '') {
            throw new \RuntimeException('A target path is required to save the image.');
        }

        $directory = dirname($targetPath);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create image directory [%s].', $directory));
        }

        $mime = self::detectMimeType($targetPath, $this->mime);
        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($this->resource, $targetPath, max(0, min(100, $quality))),
            'image/png' => imagepng($this->resource, $targetPath, (int) round((100 - max(0, min(100, $quality))) / 10)),
            'image/gif' => imagegif($this->resource, $targetPath),
            'image/webp' => imagewebp($this->resource, $targetPath, max(0, min(100, $quality))),
            default => false,
        };

        if ($saved !== true) {
            throw new \RuntimeException(sprintf('Unable to save image to [%s].', $targetPath));
        }

        $this->path = $targetPath;
        $this->mime = $mime;

        return $targetPath;
    }

    public function copy(): self
    {
        $copy = imagecreatetruecolor($this->width(), $this->height());

        if (!$copy instanceof GdImage) {
            throw new \RuntimeException('Unable to duplicate image.');
        }

        imagealphablending($copy, false);
        imagesavealpha($copy, true);
        imagecopy($copy, $this->resource, 0, 0, 0, 0, $this->width(), $this->height());

        $image = new self($copy);
        $image->path = $this->path;
        $image->mime = $this->mime;

        return $image;
    }

    public function __destruct()
    {
        $this->destroyResource($this->resource);
    }

    private function resample(
        int $targetWidth,
        int $targetHeight,
        int $srcX,
        int $srcY,
        int $srcWidth,
        int $srcHeight
    ): self {
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if (!$canvas instanceof GdImage) {
            throw new \RuntimeException('Unable to create resized image.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);

        if (!imagecopyresampled($canvas, $this->resource, 0, 0, $srcX, $srcY, $targetWidth, $targetHeight, $srcWidth, $srcHeight)) {
            $this->destroyResource($canvas);
            throw new \RuntimeException('Unable to resample image.');
        }

        $this->destroyResource($this->resource);
        $this->resource = $canvas;

        return $this;
    }

    private function destroyResource(GdImage $resource): void
    {
        if (PHP_VERSION_ID < 80500) {
            imagedestroy($resource);
        }
    }

    private static function detectMimeType(string $path, ?string $fallback = null): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension !== '') {
            return match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => $fallback ?? throw new \InvalidArgumentException(sprintf('Unsupported image format for [%s].', $path)),
            };
        }

        $mime = is_file($path) ? mime_content_type($path) : false;

        if (is_string($mime) && in_array($mime, self::SUPPORTED_MIME_TYPES, true)) {
            return $mime;
        }

        return $fallback ?? throw new \InvalidArgumentException(sprintf('Unsupported image format for [%s].', $path));
    }

    private static function allocateColor(GdImage $resource, string $hex): int
    {
        $value = ltrim(trim($hex), '#');

        if (!preg_match('/^[A-Fa-f0-9]{6}([A-Fa-f0-9]{2})?$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Color [%s] must be a 6 or 8 digit hex value.', $hex));
        }

        $red = hexdec(substr($value, 0, 2));
        $green = hexdec(substr($value, 2, 2));
        $blue = hexdec(substr($value, 4, 2));
        $alpha = strlen($value) === 8 ? 127 - (int) round((hexdec(substr($value, 6, 2)) / 255) * 127) : 0;

        return imagecolorallocatealpha($resource, $red, $green, $blue, $alpha);
    }
}
