<?php

declare(strict_types=1);

namespace Marwa\Framework\Views\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

abstract class AbstractViewExtension extends AbstractExtension
{
    /**
     * @var array<int, TwigFunction>
     */
    private array $functions = [];

    /**
     * @var array<int, TwigFilter>
     */
    private array $filters = [];

    abstract public function register(): void;

    protected function addFunction(string $name, callable $callback, bool $isSafe = false): TwigFunction
    {
        $options = $isSafe ? ['is_safe' => ['html']] : [];
        $function = new TwigFunction($name, $callback, $options);
        $this->functions[] = $function;

        return $function;
    }

    protected function addFilter(string $name, callable $callback): TwigFilter
    {
        $filter = new TwigFilter($name, $callback);
        $this->filters[] = $filter;

        return $filter;
    }

    /**
     * @return array<int, TwigFunction>
     */
    public function getFunctions(): array
    {
        $this->register();

        return $this->functions;
    }

    /**
     * @return array<int, TwigFilter>
     */
    public function getFilters(): array
    {
        $this->register();

        return $this->filters;
    }
}