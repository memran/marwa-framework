<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\Helpers;

use Psr\Http\Message\UploadedFileInterface;

final class TypeValidators
{
    public function isEmpty(mixed $value, bool $strict = false): bool
    {
        if ($value === null) {
            return true;
        }

        if ($value === '') {
            return true;
        }

        if (is_array($value)) {
            return $value === [];
        }

        if ($strict) {
            return false;
        }

        return false;
    }

    public function isInteger(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1);
    }

    public function isBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_int($value)) {
            return in_array($value, [0, 1], true);
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['0', '1', 'true', 'false', 'yes', 'no', 'on', 'off'], true);
        }

        return false;
    }

    public function isAccepted(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['yes', 'on', '1', 'true'], true);
    }

    public function isDeclined(mixed $value): bool
    {
        if (is_bool($value)) {
            return !$value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['no', 'off', '0', 'false'], true);
    }

    public function isImage(UploadedFileInterface $file): bool
    {
        $type = strtolower($file->getClientMediaType());

        return str_starts_with($type, 'image/');
    }
}
