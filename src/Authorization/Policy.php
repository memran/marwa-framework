<?php

declare(strict_types=1);

namespace Marwa\Framework\Authorization;

use Marwa\Framework\Authorization\Contracts\UserInterface;

abstract class Policy
{
    public function before(?UserInterface $user, string $ability): ?bool
    {
        return null;
    }

    protected function getUserId(?UserInterface $user): ?int
    {
        if ($user === null) {
            return null;
        }

        if (method_exists($user, 'getId')) {
            return $user->getId();
        }

        if (isset($user->id)) {
            return (int) $user->id;
        }

        return null;
    }

    protected function isOwner(?UserInterface $user, object $resource): bool
    {
        $userId = $this->getUserId($user);

        if ($userId === null) {
            return false;
        }

        if (method_exists($resource, 'getUserId')) {
            return $userId === $resource->getUserId();
        }

        if (isset($resource->user_id)) {
            return $userId === (int) $resource->user_id;
        }

        if (isset($resource->owner_id)) {
            return $userId === (int) $resource->owner_id;
        }

        return false;
    }
}