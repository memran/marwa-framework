<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\Helpers;

use Psr\Http\Message\UploadedFileInterface;

final class TypeCoercer
{
    /**
     * @param array<int, mixed> $rules
     */
    public function coerceValidatedValue(mixed $value, array $rules): mixed
    {
        foreach ($rules as $rule) {
            if (!is_string($rule)) {
                continue;
            }

            $name = strtok($rule, ':');

            if ($name === 'boolean') {
                return $this->toBoolean($value);
            }

            if ($name === 'integer') {
                return $this->toInteger($value);
            }

            if ($name === 'numeric') {
                return $this->toNumeric($value);
            }
        }

        return $value;
    }

    public function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    public function toInteger(mixed $value): int
    {
        return (int) $value;
    }

    public function toNumeric(mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $numeric = (string) $value;

        return str_contains($numeric, '.') ? (float) $numeric : (int) $numeric;
    }
}
