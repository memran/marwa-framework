<?php
	
	namespace Marwa\Application\Middlewares;
	
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	
	class SubdomainMiddleware implements MiddlewareInterface {
		
		/**
		 * Process a server request and return a response.
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			$uri = explode('.', $request->getUri()->getHost());
			if ( count($uri) > 2 && isset($uri[0]) && $uri[0] != 'www' )
			{
				return $handler->handle($request->withAttribute('subdomain', $uri[0]));
			}
			
			// middleware and ultimately reach your route callable
			return $handler->handle($request);
		}
		
	}

