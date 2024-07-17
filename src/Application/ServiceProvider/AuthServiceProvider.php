<?php

namespace Marwa\Application\ServiceProvider;

use Marwa\Application\Authentication\Auth;
use Marwa\Application\Containers\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored.
	 *
	 * @var array
	 */
	// protected $provides = [
	// 	'auth'
	// ];
	public function provides(string $id): bool
	{
		$services = [
			'auth',
		];

		return in_array($id, $services);
	}

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 */
	public function register(): void
	{
		$this->singleton('auth', new Auth(app('config')->load('auth.php')));
	}
}
