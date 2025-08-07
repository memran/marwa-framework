<?php
namespace Marwa\Application\Contracts;
interface ContainerInterface
{
    public function bind(string $abstract, mixed $concrete = null): static;
    public function singleton(string $abstract, mixed $concrete = null): static;
}
