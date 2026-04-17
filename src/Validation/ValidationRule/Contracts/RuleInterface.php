<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\Contracts;

interface RuleInterface
{
    public function name(): string;

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool;

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string;

    /**
     * @return array<string, mixed>
     */
    public function params(): array;
}
