<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation;

use Marwa\Framework\Adapters\Validation\RequestValidatorAdapter;
use Marwa\Router\Contract\ValidatorInterface;
use Marwa\Support\Validation\RequestValidator as SupportRequestValidator;
use Marwa\Support\Validation\RuleRegistry as SupportRuleRegistry;
use Psr\Http\Message\ServerRequestInterface;

final class RequestValidator implements ValidatorInterface
{
    private RequestValidatorAdapter $adapter;

    public function __construct()
    {
        $registry = new SupportRuleRegistry();
        $this->adapter = new RequestValidatorAdapter(
            new SupportRequestValidator($registry),
            $registry
        );
    }

    public function validate(ServerRequestInterface $request, array $rules): array
    {
        return $this->adapter->validate($request, $rules);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string|array<int, mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    public function validateInput(
        array $input,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): array {
        return $this->adapter->validateInput($input, $rules, $messages, $attributes);
    }

    /**
     * @param array<string, string|array<int, mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    public function validateRequest(
        ServerRequestInterface $request,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): array {
        return $this->adapter->validateRequest($request, $rules, $messages, $attributes);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string|array<int, mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @param array<string, string> $customRules
     * @return array<string, mixed>
     */
    public function validateInputWithCustomRules(
        array $input,
        array $rules,
        array $messages = [],
        array $attributes = [],
        array $customRules = []
    ): array {
        return $this->adapter->validateInputWithCustomRules($input, $rules, $messages, $attributes, $customRules);
    }

    /**
     * @param array<string, string|array<int, mixed>> $rules
     * @return array<string, string|array<int, mixed>>
     */
    public function normalize(array $rules): array
    {
        return $this->adapter->normalize($rules);
    }
}
