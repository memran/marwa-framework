<?php

declare(strict_types=1);

namespace Marwa\Application\Routes;

use Exception;
use League\Route\Dispatcher;
use League\Route\Router;
use Marwa\Application\AppStrategy;
use Marwa\Application\Exceptions\NotFoundException;
use Marwa\Application\Response;
use Marwa\Application\Utils\Finder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteCollection extends Router
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
			include_once($file);
		}


		//building the route on the RouteCollection
		$this->buildRoute();
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
	 * @return $this
	 */
	public function buildRoute(): self
	{
		if (is_null($this->getStrategy())) {
			$this->setStrategy(new AppStrategy());
		}
		$this->prepareRoutes();

		return $this;
	}

	/**
	 * Prepare all routes, build name index and filter out none matching
	 * routes before being passed off to the parser.
	 *
	 * @return void
	 */
	protected function prepareRoutes(): void
	{
		//build name INdex
		$this->buildNameIndex();
		//prepare groups
		$this->prepareGroups();
		//$routes = array_merge(array_values($this->routes), array_values($this->namedRoutes));
		$routes = $this->getAllRoutes();

		foreach ($routes as $key => $route) {
			if (is_null($route->getStrategy())) {
				$route->setStrategy($this->getStrategy());
			}
			$this->addRoute($route->getMethod(), $this->parseRoutePath($route->getPath()), $route);
		}
	}


	/**
	 * Process all groups
	 *
	 * Adds all of the group routes to the collection and determines if the group
	 * strategy should be be used.
	 *
	 * @param ServerRequestInterface $request
	 *
	 * @return void
	 */
	protected function prepareGroups(): void
	{
		//$activePath = $request->getUri()->getPath();
		foreach ($this->groups as $key => $group) {
			$group();
		}
	}


	/**
	 * [getAllRoutes description] return all routes
	 *
	 * @return array [description]
	 */
	protected function getAllRoutes(): array
	{
		return array_merge(array_values($this->routes), array_values($this->namedRoutes));
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
	 * {@inheritdoc}
	 * @throws NotFoundException
	 */
	public function dispatch(ServerRequestInterface $request): ResponseInterface
	{

		//check routeSchema/Port
		$this->routeCheckSchema($request);

		//processing the route group
		$this->processGroups($request);

		return (new Dispatcher($this->getData()))
			->middlewares((array) $this->getMiddlewareStack())
			->setStrategy($this->getStrategy())
			->dispatchRequest($request);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @throws NotFoundException
	 */
	protected function routeCheckSchema(ServerRequestInterface $request): void
	{
		$routes = $this->getAllRoutes();

		foreach ($routes as $key => $route) {
			if (!is_null($route->getScheme()) && $route->getScheme() !== $request->getUri()->getScheme()) {
				throw new NotFoundException('Route schema did not match');
			}

			// check for domain condition
			if (!is_null($route->getHost()) && $route->getHost() !== $request->getUri()->getHost()) {
				throw new NotFoundException('Route host did not match');
			}
			//check for port condition
			if (!is_null($route->getPort()) && $route->getPort() !== $request->getUri()->getPort()) {
				throw new NotFoundException('Route port did not match');
			}
		}
	}

	/**
	 * Process all groups
	 *
	 * Adds all of the group routes to the collection and determines if the group
	 * strategy should be be used.
	 *
	 * @param ServerRequestInterface $request
	 *
	 * @return void
	 */
	protected function processGroups(ServerRequestInterface $request): void
	{
		$activePath = $request->getUri()->getPath();
		foreach ($this->groups as $key => $group) {
			// we want to determine if we are technically in a group even if the
			// route is not matched so exceptions are handled correctly
			if (
				strncmp($activePath, $group->getPrefix(), strlen($group->getPrefix())) === 0
				&& !is_null($group->getStrategy())
			) {
				$this->setStrategy($group->getStrategy());
			}
			unset($this->groups[$key]);
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
