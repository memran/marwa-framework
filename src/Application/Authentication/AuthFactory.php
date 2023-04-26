<?php
	
	
	namespace Marwa\Application\Authentication;
	
	use Marwa\Application\Authentication\Adapters\AuthenticationInterface;
	use Marwa\Application\Authentication\Exceptions\InvalidAuthenticationDriver;
	
	class AuthFactory {
		
		/**
		 * @param string $adapter
		 * @param array $params
		 * @return AuthenticationInterface
		 * @throws InvalidAuthenticationDriver
		 */
		public static function getInstance( string $adapter, array $params = [] ) : AuthenticationInterface
		{
			if ( empty($adapter) )
			{
				throw new InvalidAuthenticationDriver('Invalid Authentication driver');
			}
			$object = 'Marwa\\Application\\Authentication\\Adapters\\' . ucfirst($adapter);
			
			return new $object($params);
		}
	}
