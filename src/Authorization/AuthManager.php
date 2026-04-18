<?php

declare(strict_types=1);

namespace Marwa\Framework\Authorization;

use Marwa\Framework\Authorization\Contracts\UserInterface;

class AuthManager
{
    protected ?UserInterface $user = null;

    public function __construct(
        protected Gate $gate
    ) {}

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;
        $this->gate->setUser($user);

        return $this;
    }

    public function user(): ?UserInterface
    {
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function guest(): bool
    {
        return $this->user === null;
    }

    public function id(): ?int
    {
        if ($this->user === null) {
            return null;
        }

        if (method_exists($this->user, 'getId')) {
            return $this->user->getId();
        }

        if (isset($this->user->id)) {
            return (int) $this->user->id;
        }

        return null;
    }
}
