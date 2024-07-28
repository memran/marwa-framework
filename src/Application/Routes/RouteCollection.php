<?php declare(strict_types=1);

namespace Marwa\Application\Routes;

use Exception;
use League\Route\Router as LRouter;
use Marwa\Application\AppStrategy;
use Marwa\Application\Exceptions\NotFoundException;
use Marwa\Application\Response;
use Marwa\Application\Utils\Finder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteCollection extends LRouter
{

	/**
	 * [protected description] PSR7 Request Object
	 *
	 * @var ServerRequestInterface
	 */
	protected $request;

	/**
	 * [protected description] route files path
	 *
	 * @var string
	 */
	protected $path;


	/**
	 * [protected description]
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @throws Exception
	 */
	public function registerAllRoutes(): void
	{

		/**
		 * Scan the file name in the route folders
		 * If it is found then include it
		 */
		//loop through the file lists
		foreach (Finder::findFiles('*.php')->in($this->getPath()) as $file) {
			include_once ($file);
		}
	}

	/**
	 * @return string
	 */
	protected function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @param string $path
	 * @throws RouteFileException
	 */
	public function setPath(string $path): void
	{
		if (is_null($path)) {
			throw new RouteFileException('Route path is null');
		}

		if (!is_dir($path)) {
			throw new RouteFileException('Route directory does not exists.');
		}

		$this->path = $path;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return $this
	 */
	public function setRequest(ServerRequestInterface $request)
	{
		if ($request instanceof ServerRequestInterface) {
			$this->request = $request;
		}

		return $this;
	}

	/**
	 * @return ResponseInterface
	 */
	public function getResponse(): ResponseInterface
	{
		try {
			return $this->dispatch($this->request);
		} catch (NotFoundException $exception) {
			// handles everything else, e.g. POST /foo
			return Response::error('Route Not Found :' . $exception);
		}
	}
	/**
	 * @param ContainerInterface $container
	 * @throws Exception
	 */
	public function useContainer(ContainerInterface $container): void
	{
		//checking container interface
		if (!$container instanceof ContainerInterface) {
			throw new Exception('Invalid Container Interface');
		}
		$strategy = new AppStrategy();
		$strategy->setContainer($container);
		$this->setStrategy($strategy);
	}
}
