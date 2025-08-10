<?php

namespace Marwa\App\Security;

class FileUpload
{
    /**
     * Validate the uploaded file.
     *
     * @param array $file The uploaded file information.
     * @return bool True if valid, false otherwise.
     */
    public static function validate(array $file): bool
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxSize = 10485760; // 10 MB

        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }

        if ($file['size'] > $maxSize) {
            return false;
        }

        return true;
    }

    /**
     * Sanitize the file name.
     *
     * @param string $filename The file name.
     * @return string The sanitized file name.
     */
    public static function sanitizeName(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }
}
