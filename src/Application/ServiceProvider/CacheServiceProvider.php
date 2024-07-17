<?php

namespace Marwa\Application\ServiceProvider;

use Marwa\Application\Cache\Cache;
use Marwa\Application\Containers\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{

	/**
	 * 
	 * 
	 * @param string $id
	 * @return bool
	 */
	public function provides(string $id): bool
	{
		$services = [
			'cache',
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

		$this->singleton('cache', function () {
			return new Cache(app('config')->file('cache.php')->load());
		});
	}
}
