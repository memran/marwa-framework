<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\Helpers;

final class DateValidators
{
    public function isDate(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            new \DateTimeImmutable($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function matchesDateFormat(mixed $value, string $format): bool
    {
        if ($format === '') {
            return false;
        }

        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat($format, $value);

        return $date instanceof \DateTimeImmutable && $date->format($format) === $value;
    }

    public function matchesRegex(mixed $value, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        if (@preg_match($pattern, '') === false) {
            return false;
        }

        return is_scalar($value) || $value === null
            ? preg_match($pattern, (string) $value) === 1
            : false;
    }
}
