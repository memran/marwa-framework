<?php

namespace Marwa\Application\ServiceProvider;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Marwa\Application\Containers\ServiceProvider;


class EmitterServiceProvider extends ServiceProvider
{

	/**
	 * Summary of provides
	 * @param string $id
	 * @return bool
	 */
	public function provides(string $id): bool
	{
		$services = [
			'emitter',
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
		$this->singleton('emitter', function () {
			return new SapiEmitter();
		});
	}
}
