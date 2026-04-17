<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\Helpers;

final class TransformProcessor
{
    /**
     * @param array<int, mixed> $rules
     */
    public function applyTransforms(mixed $value, array $rules, bool $exists): mixed
    {
        foreach ($rules as $rule) {
            if (!is_string($rule) || !str_contains($rule, ':')) {
                if ($rule === 'trim' && is_string($value)) {
                    $value = trim($value);
                }

                if ($rule === 'lowercase' && is_string($value)) {
                    $value = mb_strtolower($value);
                }

                if ($rule === 'uppercase' && is_string($value)) {
                    $value = mb_strtoupper($value);
                }

                continue;
            }

            [$name, $parameterString] = array_pad(explode(':', $rule, 2), 2, '');

            if ($name === 'default' && (!$exists || $value === null || $value === '')) {
                $value = $this->castDefault($parameterString);
            }
        }

        return $value;
    }

    public function castDefault(string $value): mixed
    {
        $value = trim($value);

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($value) ? ($value === (string) (int) $value ? (int) $value : (float) $value) : $value,
        };
    }
}
