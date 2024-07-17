<?php

namespace Marwa\Application\ServiceProvider;

use Marwa\Application\Containers\ServiceProvider;
use Marwa\Application\Routes\Router;

class RouteServiceProvider extends ServiceProvider
{

	public function provides(string $id): bool
	{
		$services = [
			'router',
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
		$this->singleton('router', function () {
			return Router::create(
				app('request'),
				app('route_path'),
				container()->getPsrContainer()
			);
		});

	}
}
