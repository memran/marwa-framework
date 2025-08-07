<?php

namespace Marwa\Application\ServiceProvider;

use Marwa\Application\Containers\ServiceProvider;
use Marwa\Application\Views\View;

class ViewServiceProvider extends ServiceProvider
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
			'view',
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
		$this->singleton('view', function () {
			$engine = View::getInstance(app('config')->get('view'));
			$engine->setThemeFolder(base_path('resources/views'));
			return $engine;
		});
	}
}
