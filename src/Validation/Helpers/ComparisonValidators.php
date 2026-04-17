<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\Helpers;

use Psr\Http\Message\UploadedFileInterface;

final class ComparisonValidators
{
    public function __construct(
        private ValueAccessor $accessor
    ) {}

    /**
     * @param array<string, mixed> $input
     */
    public function isConfirmed(string $field, mixed $value, array $input): bool
    {
        $exists = false;
        $confirmation = $this->accessor->getValue($input, $field . '_confirmation', $exists);

        return $exists && (string) $confirmation === (string) $value;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function sameAs(string $field, mixed $value, array $input, string $other): bool
    {
        if ($other === '') {
            return false;
        }

        $exists = false;
        $otherValue = $this->accessor->getValue($input, $other, $exists);

        return $exists && (string) $otherValue === (string) $value;
    }

    public function compareMin(mixed $value, ?string $minimum): bool
    {
        if ($minimum === null || $minimum === '') {
            return true;
        }

        $min = (float) $minimum;

        return match (true) {
            is_string($value) => mb_strlen($value) >= $min,
            is_array($value) => count($value) >= $min,
            is_numeric($value) => (float) $value >= $min,
            $value instanceof UploadedFileInterface => ($value->getSize() ?? 0) >= $min,
            default => false,
        };
    }

    public function compareMax(mixed $value, ?string $maximum): bool
    {
        if ($maximum === null || $maximum === '') {
            return true;
        }

        $max = (float) $maximum;

        return match (true) {
            is_string($value) => mb_strlen($value) <= $max,
            is_array($value) => count($value) <= $max,
            is_numeric($value) => (float) $value <= $max,
            $value instanceof UploadedFileInterface => ($value->getSize() ?? 0) <= $max,
            default => false,
        };
    }

    public function compareBetween(mixed $value, ?string $minimum, ?string $maximum): bool
    {
        return $this->compareMin($value, $minimum) && $this->compareMax($value, $maximum);
    }
}
