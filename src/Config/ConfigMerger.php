<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

final class ConfigMerger
{
    private const APPEND_PREFIX = '+';
    private const PREPEND_PREFIX = '-';
    private const REMOVE_PREFIX = '!';

    /**
     * Merge defaults with overrides, supporting additive list operations.
     *
     * List values support these prefixes:
     * - "+value" appends to the list
     * - "-value" prepends to the list
     * - "!value" removes from the list
     *
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function merge(array $defaults, array $overrides): array
    {
        $result = $defaults;

        foreach ($overrides as $key => $value) {
            if (!array_key_exists($key, $defaults)) {
                $result[$key] = $value;

                continue;
            }

            if (!is_array($defaults[$key]) || !is_array($value)) {
                $result[$key] = $value;

                continue;
            }

            $result[$key] = self::mergeLists($defaults[$key], $value);
        }

        return $result;
    }

    /**
     * @param list<mixed> $defaults
     * @param list<mixed> $overrides
     * @return list<mixed>
     */
    private static function mergeLists(array $defaults, array $overrides): array
    {
        $result = $defaults;
        $removeKeys = [];

        foreach ($overrides as $item) {
            if (!is_string($item)) {
                $result[] = $item;

                continue;
            }

            if (str_starts_with($item, self::REMOVE_PREFIX)) {
                $removeKeys[substr($item, 1)] = true;

                continue;
            }

            if (str_starts_with($item, self::PREPEND_PREFIX)) {
                array_unshift($result, substr($item, 1));

                continue;
            }

            if (str_starts_with($item, self::APPEND_PREFIX)) {
                $result[] = substr($item, 1);

                continue;
            }

            $result[] = $item;
        }

        if ([] !== $removeKeys) {
            $result = self::applyRemoves($result, $removeKeys);
        }

        return $result;
    }

    /**
     * @param list<mixed> $items
     * @param array<string, true> $removeKeys
     * @return list<mixed>
     */
    private static function applyRemoves(array $items, array $removeKeys): array
    {
        $filtered = [];

        foreach ($items as $item) {
            if (is_string($item) && isset($removeKeys[$item])) {
                continue;
            }
            $filtered[] = $item;
        }

        return $filtered;
    }

    /**
     * Check if a string indicates removal.
     */
    public static function isRemoval(string $item): bool
    {
        return str_starts_with($item, self::REMOVE_PREFIX);
    }

    /**
     * Check if a string indicates append.
     */
    public static function isAppend(string $item): bool
    {
        return str_starts_with($item, self::APPEND_PREFIX);
    }

    /**
     * Check if a string indicates prepend.
     */
    public static function isPrepend(string $item): bool
    {
        return str_starts_with($item, self::PREPEND_PREFIX);
    }

    /**
     * Get the operation type for a list item.
     */
    public static function operation(string $item): string
    {
        if (str_starts_with($item, self::REMOVE_PREFIX)) {
            return 'remove';
        }

        if (str_starts_with($item, self::PREPEND_PREFIX)) {
            return 'prepend';
        }

        if (str_starts_with($item, self::APPEND_PREFIX)) {
            return 'append';
        }

        return 'replace';
    }
}

