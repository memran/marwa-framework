# Image Processing Guide

This guide covers image manipulation using the Image class.

## Overview

The Image class provides image manipulation:

- Resize images
- Crop images
- Apply filters
- Convert formats
- Create thumbnails

## Basic Usage

### Load Image

```php
use Marwa\Framework\Supports\Image;

// From file
$image = Image::fromFile('public/avatars/user.jpg');

// From resource
$image = imagecreatefromjpeg('public/photo.jpg');
$image = Image::fromResource($image);
```

### Save Image

```php
// Save to file
$image->save('public/avatars/new.jpg');

// Save as different format
$image->save('public/avatars/new.png', 'image/png');

// Save with quality
$image->save('public/avatars/new.jpg', 'image/jpeg', 90);
```

## Resize Images

### Resize to Dimensions

```php
// Resize to exact dimensions
$image = Image::fromFile('original.jpg')
    ->resize(800, 600)
    ->save('resized.jpg');
```

### Resize by Width

```php
// Resize maintaining aspect ratio
$image = Image::fromFile('original.jpg')
    ->resize(width: 800)
    ->save('resized.jpg');
```

### Resize by Height

```php
// Resize maintaining aspect ratio
$image = Image::fromFile('original.jpg')
    ->resize(height: 600)
    ->save('resized.jpg');
```

### Resize to Fit

```php
// Fit within dimensions, maintain aspect ratio
$image = Image::fromFile('original.jpg')
    ->resize(800, 600, true)
    ->save('fit.jpg');
```

### Resize to Scale

```php
// Scale by percentage
$image = Image::fromFile('original.jpg')
    ->scale(0.5) // 50%
    ->save('scaled.jpg');
```

## Crop Images

### Crop

```php
// Crop specific area
$image = Image::fromFile('original.jpg')
    ->crop(100, 100, 200, 200)
    ->save('cropped.jpg');
```

### Crop to Center

```php
// Crop from center
$image = Image::fromFile('original.jpg')
    ->cropCenter(400, 300)
    ->save('center-crop.jpg');
```

### Crop to Square

```php
// Crop to square
$image = Image::fromFile('original.jpg')
    ->cropSquare()
    ->save('square.jpg');
```

## Rotate & Flip

### Rotate

```php
// Rotate 90 degrees
$image = Image::fromFile('original.jpg')
    ->rotate(90)
    ->save('rotated.jpg');

// Rotate with background color
$image->rotate(45, 0xFFFFFF); // White background
```

### Flip Horizontal

```php
$image = Image::fromFile('original.jpg')
    ->flipHorizontally()
    ->save('flipped.jpg');
```

### Flip Vertical

```php
$image = Image::fromFile('original.jpg')
    ->flipVertically()
    ->save('flipped.jpg');
```

## Image Filters

### Brightness

```php
// Range: -255 to 255
$image = Image::fromFile('original.jpg')
    ->brightness(30)
    ->save('bright.jpg');
```

### Contrast

```php
// Range: -255 to 255
$image = Image::fromFile('original.jpg')
    ->contrast(-30)
    ->save('contrast.jpg');
```

### Grayscale

```php
$image = Image::fromFile('original.jpg')
    ->grayscale()
    ->save('gray.jpg');
```

### Sepia

```php
$image = Image::fromFile('original.jpg')
    ->sepia()
    ->save('sepia.jpg');
```

### Blur

```php
// Gaussian blur
$image = Image::fromFile('original.jpg')
    ->blur(5)
    ->save('blur.jpg');
```

### Pixelate

```php
$image = Image::fromFile('original.jpg')
    ->pixelate(10)
    ->save('pixelated.jpg');
```

### Invert

```php
$image = Image::fromFile('original.jpg')
    ->invert()
    ->save('inverted.jpg');
```

## Text Overlay

### Add Text

```php
$image = Image::fromFile('original.jpg')
    ->text('Hello World', 20, 20, function ($style) {
        $style->size(24);
        $style->color(0xFFFFFF);
        $style->align('left');
        $style->valign('top');
    })
    ->save('with-text.jpg');
```

### Text Style

```php
$image->text('Caption', 10, 10, function ($style) {
    $style->size(20);           // Font size
    $style->color(0xFFFFFF);       // White
    $style->align('center');      // left, center, right
    $style->valign('bottom');    // top, middle, bottom
    $style->font('/path/font.ttf'); // Custom font
});
```

## Transparency

### Make Transparent

```php
$image = Image::fromFile('original.png')
    ->enableAlpha()
    ->save('transparent.png');
```

### Blend Mode

```php
$image = Image::fromFile('base.jpg')
    ->blend('overlay.png', 100, 100)
    ->save('blended.jpg');
```

## Format Conversion

### JPEG to PNG

```php
$image = Image::fromFile('photo.jpg')
    ->save('photo.png', 'image/png');
```

### PNG to JPEG

```php
$image = Image::fromFile('photo.png')
    ->grayscale()
    ->save('photo.jpg', 'image/jpeg', 95);
```

### WebP

```php
// Save as WebP
$image = Image::fromFile('photo.jpg')
    ->save('photo.webp', 'image/webp', 85);
```

## Quality & Compression

### JPEG Quality

```php
// Range: 0-100 (100 = best)
$image->save('photo.jpg', 'image/jpeg', 90);
```

### PNG Compression

```php
// Range: 0-9 (9 = best)
$image = Image::fromFile('photo.png')
    ->compress(9)
    ->save('compressed.png');
```

## Create Thumbnails

### Thumbnail Function

```php
function createThumbnail(string $source, string $dest, int $width, int $height): void
{
    Image::fromFile($source)
        ->resize($width, $height, true)
        ->save($dest, 'image/jpeg', 85);
}

// Usage
createThumbnail('uploads/photo.jpg', 'public/thumbs/photo.jpg', 200, 200);
```

### Avatar Function

```php
function createAvatar(string $source, string $dest): void
{
    Image::fromFile($source)
        ->resize(200, 200, true)
        ->cropCenter(200, 200)
        ->save($dest, 'image/jpeg', 90);
}
```

## Common Patterns

### Image Upload

```php
public function upload(Request $request): Response
{
    $file = $request->file('image');
    
    $filename = uniqid() . '.jpg';
    
    Image::fromFile($file->getPathname())
        ->resize(1200, 1200, true)
        ->save('uploads/' . $filename);
    
    // Create thumbnail
    Image::fromFile($file->getPathname())
        ->resize(300, 300, true)
        ->cropSquare()
        ->save('uploads/thumbs/' . $filename);
    
    return response()->json(['filename' => $filename]);
}
```

### Optimize Upload

```php
public function optimize(string $path): void
{
    Image::fromFile($path)
        ->grayscale()
        ->compress(9)
        ->save($path);
}
```

### Watermark

```php
public function watermark(string $source, string $watermark): void
{
    $image = Image::fromFile($source);
    $watermarkImage = Image::fromFile($watermark);
    
    // Position at bottom-right
    $x = $image->width() - $watermarkImage->width() - 20;
    $y = $image->height() - $watermarkImage->height() - 20;
    
    $image->composite($watermarkImage, $x, $y)->save($source);
}
```

## Get Image Info

### Dimensions

```php
$width = $image->width();
$height = $image->height();
```

### Get Resource

```php
$resource = $image->resource();
```

### Check Type

```php
$isPng = $image->isPng();
$isJpeg = $image->isJpeg();
$isGif = $image->isGif();
```

## Supported Formats

| Format | MIME Type | Extension |
|--------|----------|----------|
| JPEG | image/jpeg | .jpg, .jpeg |
| PNG | image/png | .png |
| GIF | image/gif | .gif |
| WebP | image/webp | .webp |

## Troubleshooting

### Out of Memory

1. Process large images in chunks
2. Increase PHP memory limit
3. Use streaming for very large files

### Invalid Image

1. Check file exists and is readable
2. Verify format is supported

### Poor Quality

1. Increase quality parameter
2. Use PNG for graphics
3. Use JPEG for photos

## Related

- [Storage](storage.md) - File storage
- [File Uploads](uploads.md) - Handling uploads
- [Caching](caching.md) - Cache processed images