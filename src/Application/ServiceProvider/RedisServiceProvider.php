<?php

namespace Marwa\Application\ServiceProvider;

use Marwa\Application\Cache\Cache;
use Marwa\Application\Containers\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{

	public function provides(string $id): bool
	{
		$services = [
			'redis',
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

		$this->singleton('redis', function () {
			$cache = new Cache(app('config')->file('file.php')->load());

			return $cache->disk('redis');
		});
	}
}
