<?php

	namespace Marwa\Application\Cache;
	
	class Cache implements CacheInterface {
		
		/**
		 * [protected description]
		 *
		 * @var string
		 */
		protected $_disk = 'file';
		
		/**
		 * @var array|mixed
		 */
		protected $_config = [];
		
		/**
		 * [protected description] cach object
		 *
		 * @var [type]
		 */
		protected $_cache;
		
		/**
		 * Cache constructor.
		 *
		 * @param array $config
		 * @throws InvalidArgumentException
		 */
		public function __construct( array $config )
		{
			$this->_config = $this->getCacheConfig($config);
		}
		
		/**
		 * @param array $config
		 * @return array
		 * @throws InvalidArgumentException
		 */
		protected function getCacheConfig(?array $config=[] ) : array
		{
			if ( empty($config) || is_null($config) )
			{
				throw new InvalidArgumentException('Invalid cache configuration');
			}
			
			return $config;
		}
		
		/**
		 * @param string $disk
		 * @throws InvalidArgumentException
		 */
		public function disk(?string $disk=null)
		{
			if ( is_null($disk) || empty($disk) )
			{
				throw new InvalidArgumentException("Invalid disk name");
			}
			$this->_disk = $disk;
			
			return $this;
		}
		
		/**
		 * @return \MatthiasMullie\Scrapbook\Psr16\SimpleCache
		 * @throws InvalidArgumentException
		 */
		public function simple()
		{
			return SimpleCache::getInstance($this->getCacheAdapter());
		}
		
		/**
		 * @return mixed
		 * @throws InvalidArgumentException
		 */
		public function getCacheAdapter()
		{
			if ( !isset($this->_cache) )
			{
				$this->createCacheAdapter();
			}
			
			return $this->_cache;
		}
		
		/**
		 * @throws InvalidArgumentException
		 */
		public function createCacheAdapter() : void
		{
			$adapter = CacheFactory::create($this->getDisk());
			$director = new CacheDirector($adapter, $this->getAdapterConfig());
			$this->_cache = $director->build()->getAdapter();
		}
		
		/**
		 * @return string
		 */
		public function getDisk()
		{
			return $this->_disk;
		}
		
		/**
		 * @return array
		 */
		public function getAdapterConfig() : array
		{
			if ( array_key_exists($this->getDisk(), $this->_config) )
			{
				return $this->_config[ $this->getDisk() ];
			}
			
			return [];
		}
		
		/**
		 * @param  $method
		 * @param  $params
		 * @return mixed
		 */
		public function __call( $method, $params )
		{
			$cache = $this->getCacheAdapter();
			
			return call_user_func_array([$cache, $method], $params);
			
		}
		
		/**
		 * @return array
		 */
		protected function getConfig() : array
		{
			return $this->_config;
		}
		
	}
