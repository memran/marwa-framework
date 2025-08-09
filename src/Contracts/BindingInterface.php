<?php declare(strict_types=1);

namespace Marwa\App\Contracts;

interface BindingInterface
{
    /**
     * Bind a service into the container.
     *
     * @param string $abstract
     * @param mixed|null $concrete
     * @return static
     */
    public function bind(string $abstract, mixed $concrete = null): static;

    /**
     * Bind a singleton service into the container.
     *
     * @param string $abstract
     * @param mixed|null $concrete
     * @return static
     */
    public function singleton(string $abstract, mixed $concrete = null): static;
}