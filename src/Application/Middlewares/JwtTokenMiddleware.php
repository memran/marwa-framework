<?php
	
	namespace Marwa\Application\Middlewares;
	
	use Marwa\Application\Facades\Auth;
	use Marwa\Application\Response;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	
	class JwtTokenMiddleware implements MiddlewareInterface {
		
		/**
		 * {@inheritdoc}
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			if ( $request->hasHeader('Authorization') )
			{
				$bearer = $request->getHeaderLine("Authorization");
				//$authToken = explode(" ", $bearer);
				$authToken = $this->getBearerToken($bearer);
				if ( is_null($authToken) )
				{
					return $this->deny();
				}
				$result = Auth::isValid();
				if ( $result )
				{
					$response = $handler->handle($request);
					
					return $response->withHeader('Authorization', 'Bearer ' . $authToken);
				}
				else
				{
					return $this->deny();
				}
			}
			
			return $this->deny();
			//return $handler->handle($request);
		}
		
		/**
		 * get access token from header
		 * @param $headers
		 * @return mixed|null
		 */
		function getBearerToken( $headers )
		{
			//$headers = getAuthorizationHeader();
			// HEADER: Get the access token from the header
			if ( !empty($headers) )
			{
				if ( preg_match('/Bearer\s(\S+)/', $headers, $matches) )
				{
					return $matches[1];
				}
			}
			
			return null;
		}
		
		/**
		 * @return ResponseInterface
		 */
		protected function deny()
		{
			return Response::error("Access Forbidden", 401);
		}
	}
