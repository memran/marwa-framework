<?php

namespace Marwa\Application\ServiceProvider;

use Marwa\Application\Configs\Config;
use Marwa\Application\Containers\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{

	/**
	 * Summary of provides
	 * @param string $id
	 * @return bool
	 */
	public function provides(string $id): bool
	{
		$services = [
			'config',
		];

		return in_array($id, $services);
	}


	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @throws \Marwa\Application\Exceptions\InvalidArgumentException
	 */
	public function register(): void
	{

		$this->singleton('config', function () {
			$instance = new Config(config_path(), env('CONFIG_CACHE'), private_storage().DS . 'cache/config.cache.php');
			$instance->load();
			return $instance;
		});
	}
}
