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

    public function login(UserInterface $user): self
    {
        $this->setUser($user);

        return $this;
    }

    public function logout(): self
    {
        $this->setUser(null);

        return $this;
    }

    public function authenticate(string $email, string $password): bool
    {
        $userModel = $this->gate->getUser();

        if ($userModel === null) {
            return false;
        }

        $className = $userModel::class;

        if (!in_array(Authenticatable::class, class_uses($className), true)) {
            return false;
        }

        if (!method_exists($className, 'authenticate')) {
            return false;
        }

        $user = $className::authenticate($email, $password);

        if ($user === null) {
            return false;
        }

        $this->setUser($user);

        return true;
    }

    public function using(?string $className): self
    {
        if ($className === null) {
            return $this;
        }

        if (!class_exists($className)) {
            return $this;
        }

        if (!in_array(UserInterface::class, class_implements($className), true)) {
            return $this;
        }

        $this->gate->setUser(new $className());

        return $this;
    }
}
