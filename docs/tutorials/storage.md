# Storage Guide

This guide covers file storage and management using the Storage facade.

## Overview

The storage system provides a unified API for file operations:

- Local file storage
- Multiple disk support
- File uploads
- Directory management

## Configuration

### config/storage.php

```php
return [
    'default' => 'local',

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'visibility' => 'public',
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
            'url' => '/storage',
        ],

        'uploads' => [
            'driver' => 'local',
            'root' => storage_path('uploads'),
            'visibility' => 'private',
        ],
    ],
];
```

## Basic Usage

### Using Storage Facade

```php
use Marwa\Framework\Facades\Storage;

// Write file
Storage::put('file.txt', 'Hello World');

// Read file
$content = Storage::get('file.txt');

// Check exists
if (Storage::exists('file.txt')) {
    // ...
}

// Delete file
Storage::delete('file.txt');
```

## File Operations

### Write Files

```php
// Write string
Storage::put('file.txt', 'Content');

// Write with visibility
Storage::put('file.txt', 'Content', 'private');

// Append to file
Storage::append('file.txt', 'More content');

// Create if not exists
Storage::put('new.txt', 'Content');
```

### Read Files

```php
// Read entire file
$content = Storage::get('file.txt');

// Read as lines
$lines = Storage::lines('file.txt');

// Get file info
$info = Storage::getMetadata('file.txt');
```

### Check Files

```php
// Check exists
Storage::exists('file.txt');

// Check is file
Storage::isFile('file.txt');

// Check is directory
Storage::isDirectory('folder/');
```

### Delete Files

```php
// Delete single file
Storage::delete('file.txt');

// Delete multiple files
Storage::delete(['file1.txt', 'file2.txt']);
```

## Directory Operations

### List Contents

```php
// List all files in directory
$files = Storage::files('avatars');

// List all directories
$dirs = Storage::directories('uploads/');

// List recursively
$all = Storage::allFiles('documents/');
```

### Create Directories

```php
// Create directory
Storage::makeDirectory('avatars');

// Create nested directories
Storage::makeDirectory('uploads/year/month');
```

### Delete Directories

```php
// Delete directory (with contents)
Storage::deleteDirectory('temp/');
```

## Disk Operations

### Using Multiple Disks

```php
// Use specific disk
Storage::disk('public')->put('file.txt', 'Content');

// Get from disk
$content = Storage::disk('public')->get('file.txt');

// Check disk exists
Storage::disk('s3')->exists('file.txt');
```

### Switch Default Disk

```php
// Change default disk
Storage::disk('uploads')->put('file.txt', 'Content');
```

### Get Path

```php
// Get absolute path
$path = Storage::path('file.txt');

// For specific disk
$path = Storage::disk('public')->path('avatars/user.jpg');
```

## File Uploads

### Handle Upload

```php
public function upload(Request $request): Response
{
    $file = $request->file('avatar');
    
    // Store uploaded file
    $path = $file->store('avatars');
    
    // With custom name
    $path = Storage::putFileAs('avatars', $file, 'avatar.jpg');
    
    return response()->json(['path' => $path]);
}
```

### Upload Methods

```php
// Store with auto-generated name
$path = $file->store('avatars');

// Store with original name
$path = $file->storeAs('avatars', $file);

// Store directly
Storage::put('avatars/' . $file->getClientOriginalName(), $file->getContents());
```

### File Validation

```php
public function upload(Request $request): Response
{
    $request->validate([
        'avatar' => 'required|file|image|max:2048',
    ]);
    
    // Continue...
}
```

## Visibility & Permissions

### Set Visibility

```php
// Set public
Storage::put('file.txt', 'Content', 'public');

// Set private
Storage::put('secret.txt', 'Content', 'private');
```

### Get Visibility

```php
$visibility = Storage::getVisibility('file.txt');
```

## Copy & Move

### Copy File

```php
// Copy to new location
Storage::copy('source.txt', 'destination.txt');

// Copy to different disk
Storage::disk('backup')->copy('file.txt', 'file.txt');
```

### Move File

```php
// Move to new location
Storage::move('old.txt', 'new.txt');
```

## Metadata

### Get File Metadata

```php
$meta = Storage::getMetadata('file.txt');
// [
//     'path' => 'file.txt',
//     'size' => 1024,
//     'last_modified' => timestamp,
//     'visibility' => 'public',
// ]
```

### Get Size

```php
$size = Storage::size('file.txt');
```

### Get Last Modified

```php
$modified = Storage::lastModified('file.txt');
```

## Streaming

### Download File

```php
public function download(): Response
{
    return Storage::download('file.txt');
}

// With custom name
return Storage::download('file.txt', 'custom-name.txt');
```

### Get File Contents

```php
// Get as string
$contents = Storage::get('file.txt');

// Get as resource
$stream = Storage::readStream('file.txt');
```

## Temporary URLs

### Generate URL

```php
// Get temporary URL (for S3, etc.)
$url = Storage::temporaryUrl('file.txt', now()->addMinutes(5));
```

## Common Patterns

### Avatar Upload

```php
public function updateAvatar(Request $request): Response
{
    $file = $request->file('avatar');
    
    // Delete old avatar if exists
    Storage::delete('avatars/' . auth()->user()->avatar);
    
    // Store new avatar with user ID in name
    $path = $file->storeAs('avatars', auth()->id() . '.jpg');
    
    // Update user
    auth()->user()->update(['avatar' => basename($path)]);
    
    return response()->json(['avatar' => $path]);
}
```

### Export File

```php
public function export(): Response
{
    // Generate export file
    $data = exportUsersAsCsv();
    Storage::put('exports/users.csv', $data);
    
    // Return for download
    return Storage::download('exports/users.csv');
}
```

## Troubleshooting

### File Not Found

1. Check path is correct:
```php
Storage::exists('path/to/file.txt');
```

2. Check disk configuration:
```php
Storage::disk('public')->exists('file.txt');
```

### Permission Denied

1. Check directory permissions:
```bash
chmod -R 775 storage/
```

2. Check ownership:
```bash
sudo chown -R www-data:www-data storage/
```

## Console Commands

```bash
# Clear storage
php marwa storage:clear

# List files
php marwa storage:list
```

## Related

- [Caching](caching.md) - Cache system
- [HTTP Client](http-client.md) - External requests
- [Deployment](../recipes/deployment.md) - Production setup