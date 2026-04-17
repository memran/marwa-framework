<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation;

use Marwa\Framework\Validation\ValidationRule\AbstractRule;
use Marwa\Framework\Validation\ValidationRule\Contracts\RuleInterface;
use Marwa\Framework\Validation\ValidationRule\ComparisonRules\BetweenRule;
use Marwa\Framework\Validation\ValidationRule\ComparisonRules\ConfirmedRule;
use Marwa\Framework\Validation\ValidationRule\ComparisonRules\InRule;
use Marwa\Framework\Validation\ValidationRule\ComparisonRules\MaxRule;
use Marwa\Framework\Validation\ValidationRule\ComparisonRules\MinRule;
use Marwa\Framework\Validation\ValidationRule\ComparisonRules\SameRule;
use Marwa\Framework\Validation\ValidationRule\DateRules\DateFormatRule;
use Marwa\Framework\Validation\ValidationRule\DateRules\DateRule;
use Marwa\Framework\Validation\ValidationRule\DateRules\RegexRule;
use Marwa\Framework\Validation\ValidationRule\TransformRules\DefaultRule;
use Marwa\Framework\Validation\ValidationRule\TransformRules\LowercaseRule;
use Marwa\Framework\Validation\ValidationRule\TransformRules\TrimRule;
use Marwa\Framework\Validation\ValidationRule\TransformRules\UppercaseRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\AcceptedRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\ArrayRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\BooleanRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\DeclinedRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\EmailRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\FileRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\FilledRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\ImageRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\IntegerRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\NumericRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\PresentRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\RequiredRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\StringRule;
use Marwa\Framework\Validation\ValidationRule\TypeRules\UrlRule;

final class RuleRegistry
{
    /**
     * @var array<string, class-string<RuleInterface>>
     */
    private array $rules = [];

    public function __construct()
    {
        $this->registerDefaultRules();
    }

    public function register(string $name, string $class): void
    {
        $this->rules[$name] = $class;
    }

    /**
     * @param array<string, class-string<RuleInterface>> $rules
     */
    public function registerMany(array $rules): void
    {
        foreach ($rules as $name => $class) {
            $this->register($name, $class);
        }
    }

    public function get(string $name): ?string
    {
        return $this->rules[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->rules[$name]);
    }

    /**
     * @return array<string, class-string<RuleInterface>>
     */
    public function all(): array
    {
        return $this->rules;
    }

    public function resolve(string $name, string $params = ''): ?RuleInterface
    {
        $class = $this->get($name);

        if ($class === null) {
            return null;
        }

        return $this->instantiateRule($class, $params);
    }

    private function instantiateRule(string $class, string $params): RuleInterface
    {
        $rule = new $class($params);

        if (!$rule instanceof RuleInterface) {
            throw new \InvalidArgumentException(
                sprintf('Class %s must implement %s', $class, RuleInterface::class)
            );
        }

        return $rule;
    }

    private function registerDefaultRules(): void
    {
        $this->registerMany([
            'required' => RequiredRule::class,
            'present' => PresentRule::class,
            'filled' => FilledRule::class,
            'string' => StringRule::class,
            'integer' => IntegerRule::class,
            'numeric' => NumericRule::class,
            'boolean' => BooleanRule::class,
            'array' => ArrayRule::class,
            'email' => EmailRule::class,
            'url' => UrlRule::class,
            'accepted' => AcceptedRule::class,
            'declined' => DeclinedRule::class,
            'file' => FileRule::class,
            'image' => ImageRule::class,
            'confirmed' => ConfirmedRule::class,
            'same' => SameRule::class,
            'in' => InRule::class,
            'min' => MinRule::class,
            'max' => MaxRule::class,
            'between' => BetweenRule::class,
            'date' => DateRule::class,
            'date_format' => DateFormatRule::class,
            'regex' => RegexRule::class,
            'default' => DefaultRule::class,
            'trim' => TrimRule::class,
            'lowercase' => LowercaseRule::class,
            'uppercase' => UppercaseRule::class,
        ]);
    }
}
