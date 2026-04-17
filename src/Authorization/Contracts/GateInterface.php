<?php

declare(strict_types=1);

namespace Marwa\Framework\Authorization\Contracts;

interface GateInterface
{
    public function authorize(string $ability, mixed $resource = null): bool;

    public function check(string $ability, mixed $resource = null): bool;

    public function denies(string $ability, mixed $resource = null): bool;

    public function allows(string $ability, mixed $resource = null): bool;

    public function forUser(UserInterface $user): GateInterface;

    public function before(callable $callback): self;

    public function define(string $ability, callable $callback): self;
}