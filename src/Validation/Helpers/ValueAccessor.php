<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\Helpers;

final class ValueAccessor
{
    /**
     * @param array<string, mixed> $input
     */
    public function hasValue(array $input, string $field): bool
    {
        $exists = false;
        $this->getValue($input, $field, $exists);

        return $exists;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getValue(array $input, string $field, bool &$exists): mixed
    {
        $exists = false;
        $current = $input;

        foreach (explode('.', $field) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $exists = true;
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setValue(array &$data, string $field, mixed $value): void
    {
        $segments = explode('.', $field);
        $current = &$data;

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }

            if ($index === array_key_last($segments)) {
                $current[$segment] = $value;
                return;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    /**
     * @param array<int, mixed> $rules
     */
    public function fieldHasDefault(array $rules): bool
    {
        foreach ($rules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'default:')) {
                return true;
            }
        }

        return false;
    }
}
