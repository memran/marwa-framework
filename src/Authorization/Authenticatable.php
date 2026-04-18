<?php

declare(strict_types=1);

namespace Marwa\Framework\Authorization;

use Marwa\Framework\Authorization\Contracts\UserInterface;

/**
 * Trait for user models that support authentication.
 *
 * Use this trait in classes that implement UserInterface
 * to add role/permission checks and authentication methods.
 */

trait Authenticatable
{
    abstract public static function findByEmail(string $email): ?self;

    abstract public static function findById(int $id): ?self;

    abstract public function getId(): ?int;

    abstract public function getEmail(): ?string;

    abstract public function getPasswordHash(): ?string;

    abstract public function getRoles(): array;

    abstract public function getPermissions(): array;

    public function hasPermission(string $permission): bool
    {
        $perms = $this->getPermissions();

        return in_array($permission, $perms, true);
    }

    public function hasRole(string $role): bool
    {
        $roles = $this->getRoles();

        return in_array($role, $roles, true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if (!$this->hasPermission($perm)) {
                return false;
            }
        }

        return true;
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function can(string $permission): bool
    {
        return $this->hasPermission($permission);
    }

    public function cannot(string $permission): bool
    {
        return !$this->can($permission);
    }

    public static function authenticate(string $email, string $password): ?self
    {
        $user = static::findByEmail($email);

        if ($user === null) {
            return null;
        }

        $hash = $user->getPasswordHash();

        if ($hash === null || !password_verify($password, $hash)) {
            return null;
        }

        return $user;
    }
}
