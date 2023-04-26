<?php

	namespace Marwa\Application\Request;

	use Psr\Http\Message\RequestInterface;

	class Request {

		/**
		 * @param string $type
		 * @return RequestInterface
		 */
		public static function create( string $type = "zend" ) : RequestInterface
		{
			return RequestFactory::create($type)->getRequest();
		}
	}
