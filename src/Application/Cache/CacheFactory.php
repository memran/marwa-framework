<?php
	
	
	namespace Marwa\Application\Cache;
	
	use Marwa\Application\Cache\Builders\File;
	use Marwa\Application\Cache\Builders\Memcache;
	use Marwa\Application\Cache\Builders\RedisServer;
	
	class CacheFactory implements FactoryInterface {
		
		/**
		 * @param string $type
		 * @return ApcCache|File|Memcache|RedisServer|MemoryCache
		 * @throws \Exception
		 */
		public static function create( string $type )
		{
			switch ( strtolower($type) )
			{
				case 'file':
					return new File();
				case 'redis':
					return new RedisServer();
				case 'memcached':
					return new Memcache();
				default:
					throw new \Exception('Invalid Cache Server');
			}
		}
	}
