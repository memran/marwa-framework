<?php
	
	namespace Marwa\Application\Containers;
	
	use League\Container\Container as LeagueContainer;
	use League\Container\ReflectionContainer;
	use Psr\Container\ContainerInterface as PContainerInterface;
	
	class Container implements ContainerInterface {
		
		/**
		 * @var null
		 */
		private static $__instance;
		
		/**
		 * @var LeagueContainer
		 */
		private $_container;
		
		/**
		 * Container constructor.
		 */
		private function __construct()
		{
			$this->_container = new LeagueContainer();
		}
		
		/**
		 * @return ContainerInterface
		 */
		public static function getInstance() : ContainerInterface
		{
			if ( static::$__instance == null )
			{
				static::$__instance = new Container();
			}
			
			return static::$__instance;
		}
		
		/**
		 *
		 */
		public function getPsrContainer() : PContainerInterface
		{
			return $this->_container;
		}
		
		/**
		 * @param string $id
		 * @param null $concrete
		 * @param bool|null $shared
		 */
		public function bind( string $id, $concrete = null, bool $shared = null )
		{
			$this->_container->add($id, $concrete, $shared);
		}
		
		/**
		 * @param string $id
		 * @param null $concrete
		 */
		public function singleton( string $id, $concrete = null )
		{
			$this->_container->share($id, $concrete);
		}
		
		/**
		 * @param  $id
		 * @param bool $new
		 * @return array|mixed|object
		 * @throws \Marwa\Application\Containers\NotFoundException
		 */
		public function get( $id, bool $new = false )
		{
			try
			{
				return $this->_container->get($id, $new);
			} catch ( \Throwable $th )
			{
				throw new NotFoundException($th->getMessage() . " >>> " . $id);
			}
		}
		
		/**
		 * @param bool $cache
		 */
		public function enableAutoWire( bool $cache = false ) : ContainerInterface
		{
			if ( $cache )
			{
				$this->_container->delegate(( new ReflectionContainer )->cacheResolutions());
			}
			else
			{
				$this->_container->delegate(new ReflectionContainer);
			}
			
			return $this;
		}
		
		/**
		 * @param  $provider
		 * @throws \Exception
		 */
		public function addServiceProvider( $provider ) : ContainerInterface
		{
			$this->_container->addServiceProvider($provider);
			
			return $this;
		}
		
		/**
		 * @param $name
		 * @param $arguments
		 * @return mixed
		 */
		public function __call( $name, $arguments )
		{
			return call_user_func_array([$this->_container, $name], $arguments);
		}
		
	}
