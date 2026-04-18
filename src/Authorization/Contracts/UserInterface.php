<?php

declare(strict_types=1);

namespace Marwa\Framework\Authorization\Contracts;

interface UserInterface
{
    public function hasPermission(string $permission): bool;

    public function hasRole(string $role): bool;
}
