<?php


namespace Marwa\Application\Authentication\Adapters;


interface AuthenticationInterface
{

    public function check(string $username, string $password): bool;
    public function verify(string $password, string $hash) : bool;
    public function getAuthenticateUser(): array;
	public function checkRemember(string $token) : bool ;
}
