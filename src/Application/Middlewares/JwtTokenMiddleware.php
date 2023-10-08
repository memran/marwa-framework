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

				$authToken = $this->getBearerToken($bearer);
				if ( is_null($authToken) )
				{
					return $this->deny(["message"=>"Token is null","code"=>"404"]);
				}
				$res=Auth::isValid($authToken);
				if ( $res['code']=="200")
				{
					$response = $handler->handle($request);
					
					return $response->withHeader('Authorization', 'Bearer ' . $authToken);
				}
				else {
					if(!$res['error'])
					{
						return $this->deny(["message"=>$res["error"],"code"=>$res['code']]);
					}
					return $this->deny(["message"=>$res["message"],"code"=>$res['code']]);
				}
			}else {
				return $this->deny(["message"=>"Authorization header not present","code"=>"404"]);
			}
			return $handler->handle($request);
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
		protected function deny($msg)
		{
			return Response::json($msg);
		}
	}
