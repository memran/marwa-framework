<?php

namespace Marwa\Application\ServiceProvider;

use Marwa\Application\Containers\ServiceProvider;
use Marwa\Application\Response;

class ResponseServiceProvider extends ServiceProvider
{

	public function provides(string $id): bool
	{
		$services = [
			'response',
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
		$this->singleton('response', function () {
			return Response::getInstance();
		});

	}
}
