<?php
declare(strict_types=1);

namespace Marwa\Application\ServiceProvider;

use Marwa\Application\Containers\ServiceProvider;
use Marwa\Application\Filesystems\Filesystem;


class FileServiceProvider extends ServiceProvider
{

	public function provides(string $id): bool
	{
		$services = [
			'file',
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
		$this->singleton('file', function () {
			return new Filesystem(app('config')->file('storage.php')->load());
		});
	}
}
