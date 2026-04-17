<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\Helpers;

use Psr\Http\Message\ServerRequestInterface;

final class InputNormalizer
{
    /**
     * @param array<string, string|array<int, mixed>> $rules
     * @return array<string, string|array<int, mixed>>
     */
    public function normalizeRules(array $rules): array
    {
        $normalized = [];

        foreach ($rules as $field => $definition) {
            $normalized[(string) $field] = is_string($definition)
                ? $definition
                : (array) $definition;
        }

        return $normalized;
    }

    /**
     * @param string|array<int, mixed> $definition
     * @return array<int, mixed>
     */
    public function normalizeFieldRules(string|array $definition): array
    {
        if (is_string($definition)) {
            $rules = array_filter(array_map('trim', explode('|', $definition)));

            return array_values($rules);
        }

        $rules = [];

        foreach ($definition as $rule) {
            if (is_string($rule) && str_contains($rule, '|')) {
                foreach (explode('|', $rule) as $part) {
                    $part = trim($part);
                    if ($part !== '') {
                        $rules[] = $part;
                    }
                }

                continue;
            }

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function extractInput(ServerRequestInterface $request): array
    {
        $input = [];
        $query = $request->getQueryParams();
        $input = array_replace_recursive($input, $query);

        $body = $request->getParsedBody();
        if (is_array($body)) {
            $input = array_replace_recursive($input, $body);
        }

        $files = $request->getUploadedFiles();
        if ($files !== []) {
            $input = array_replace_recursive($input, $files);
        }

        $params = $request->getAttribute('params');
        if (is_array($params)) {
            $input = array_replace_recursive($input, $params);
        }

        return $input;
    }
}
