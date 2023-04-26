<?php
	
	
	namespace Marwa\Application\Cache;
	
	use Marwa\Application\Cache\Builders\BuilderInterface;
	
	class CacheDirector {
		
		/**
		 * @var BuilderInterface
		 */
		protected $_builder;
		
		/**
		 * @var array
		 */
		protected $_config;
		
		/**
		 * CacheDirector constructor.
		 *
		 * @param BuilderInterface $builder
		 * @param array $config
		 * @throws InvalidArgumentException
		 */
		public function __construct( BuilderInterface $builder, array $config )
		{
			if ( empty($config) )
			{
				throw new InvalidArgumentException('Invalid cache configuration');
			}
			$this->_config = $config;
			
			$this->_builder = $builder;
		}
		
		public function build()
		{
			switch ( $this->_builder->getType() )
			{
				case 'file':
					$this->buildLocal();
					break;
				case 'redis':
					$this->buildRedis();
					break;
				case 'memcached':
					$this->buildMemcache();
					break;
				default:
					throw new \Exception('Invalid Cache Builder type');
			}
			
			return $this;
		}
		
		/**
		 *
		 */
		protected function buildLocal()
		{
			$this->_builder->setStorage($this->getConfig()['path']);
			$this->_builder->setExpire($this->getConfig()['expire']);
		}
		
		/**
		 * @return array
		 */
		protected function getConfig()
		{
			return $this->_config;
		}
		
		/**
		 *
		 */
		protected function buildRedis()
		{
			$this->_builder->setHost($this->getConfig()['host']);
			$this->_builder->setPort($this->getConfig()['port']);
		}
		
		/**
		 *
		 */
		protected function buildMemcache()
		{
			$this->_builder->setHost($this->getConfig()['host']);
			$this->_builder->setPort($this->getConfig()['port']);
		}
		
		/**
		 * @return mixed
		 */
		public function getAdapter()
		{
			return $this->_builder->getAdapter();
		}
	}
