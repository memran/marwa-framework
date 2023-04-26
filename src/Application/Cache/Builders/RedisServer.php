<?php
	/**
	 * @author    Mohammad Emran <memran.dhk@gmail.com>
	 * @copyright 2018
	 *
	 * @see https://www.github.com/memran
	 * @see http://www.memran.me
	 */
	
	namespace Marwa\Application\Cache\Builders;
	
	use MatthiasMullie\Scrapbook\Adapters\Redis as RedisAdapter;
	use Redis;
	
	class RedisServer implements BuilderInterface {
		
		/**
		 * [protected description] redis port
		 *
		 * @var int
		 */
		protected $_port = 6379;
		/**
		 * [protected description] redis host
		 *
		 * @var string
		 */
		protected $_host = '127.0.0.1';
		
		public function getType() : string
		{
			return 'redis';
		}
		
		/**
		 * @return RedisAdapter
		 */
		public function getAdapter()
		{
			return new RedisAdapter($this->getRedisServer());
		}
		
		/**
		 * @return Redis
		 */
		public function getRedisServer()
		{
			if ( !extension_loaded('redis') )
			{
				throw new \Exception("Redis Extension not installed");
			}
			
			$client = new Redis();
			$client->connect($this->getHost(), $this->getPort());
			
			return $client;
		}
		
		/**
		 * @return string
		 */
		public function getHost() : string
		{
			return $this->_host;
		}
		
		/**
		 * @param string $host
		 */
		public function setHost( string $host ) : void
		{
			$this->_host = $host;
		}
		
		/**
		 * @return int
		 */
		public function getPort() : int
		{
			return $this->_port;
		}
		
		/**
		 * @param int $port
		 */
		public function setPort( int $port ) : void
		{
			$this->_port = $port;
		}
		
	}
