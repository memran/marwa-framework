<?php
declare(strict_types=1);

namespace Marwa\Application\Routes;

use Exception;
use Laminas\Diactoros\Request;
use League\Route\RouteCollectionInterface;
use Marwa\Application\Containers\Container;

class Router
{

	/**
	 * @var RouteCollection
	 */
	protected $routeCollection;

	/**
	 * @var Request
	 */
	protected $request;
	/**
	 * @var string
	 */
	protected $path;
	/**
	 * @var Container
	 */
	protected $container;

	/**
	 * Router constructor.
	 * @throws Exception
	 */
	public function __construct()
	{

	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public static function create($request, $path, $container = null)
	{
		return (new Router())->setRequest($request)->path($path)->setContainer($container)->getRouter();
	}

	/**
	 * @return RouteCollection
	 * @throws Exception
	 */
	public function getRouter()
	{
		if (is_null($this->routeCollection)) {
			$this->setRouter();
		}
		return $this->routeCollection;
	}

	/**
	 * @param $request
	 * @return $this
	 */
	public function setRequest($request)
	{
		$this->request = $request;
		return $this;
	}

	/**
	 * @return Psr\Http\Message\ServerRequestInterface
	 * @throws Exception
	 */
	public function getRequest()
	{
		if (is_null($this->request)) {
			throw new Exception("Request class not found");
		}
		return $this->request;
	}

	/**
	 * @param $path
	 * @return $this
	 */
	public function path($path)
	{
		$this->path = $path;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return $this
	 * @throws RouteFileException
	 * @throws Exception
	 */
	public function setRouter(): self
	{
		$this->routeCollection = new RouteCollection();

		if (!$this->routeCollection instanceof RouteCollectionInterface) {
			throw new Exception('RouteCollectionInterface not implemented');
		}
		$this->routeCollection->useContainer($this->getContainer());
		$this->routeCollection->setRequest($this->getRequest());
		$this->routeCollection->setPath($this->getPath());
		return $this;
	}

	/**
	 * @param $container
	 * @return $this
	 */
	public function setContainer($container)
	{
		$this->container = $container;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getContainer()
	{
		return $this->container;
	}


}