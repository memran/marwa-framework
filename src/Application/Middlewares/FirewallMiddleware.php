<?php
	
	namespace Marwa\Application\Middlewares;
	
	use Marwa\Application\Response;
	use Marwa\Application\Utils\Filter;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	
	class FirewallMiddleware implements MiddlewareInterface {
		
		/**
		 * @var array|null
		 */
		private $whitelist = ['localhost', '127.0.0.1', '::1'];
		
		/**
		 * [private description] indicates if firewall is enable
		 *
		 * @var bool
		 */
		private $checkFirewall = false;
		/**
		 * @var string|null
		 */
		private $ipAttribute = 'client-ip';
		
		/**
		 * Constructor. Set the whitelist.
		 */
		public function __construct()
		{
			$wlist = env("WHITELIST");
			if ( !is_null($wlist) )
			{
				$this->strToIpArray($wlist);
			}
		}
		
		/**
		 * [strToIpArray description] convert string to ip array
		 *
		 * @return void [description]
		 */
		private function strToIpArray( $str )
		{
			$iplist = explode($str, ',');
			foreach ( $iplist as $ip )
			{
				if ( $this->valideIp($ip) )
				{
					$this->whitelist[] = $ip;
					$this->checkFirewall = true;
				}
			}
		}
		
		/**
		 * [valideIp description] return ip if valid status
		 *
		 * @param string $ip [description]
		 * @return bool
		 */
		private function valideIp( $ip )
		{
			return Filter::validIp($ip);
		}
		
		/**
		 * Process a server request and return a response.
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			$ip = $this->getIp($request);
			
			if ( empty($ip) )
			{
				return Response::html('<h1>Sorry IP Not Found!</h1>', 403);
			}
			
			//if whitelist is not null
			if ( $this->checkFirewall )
			{
				if ( !$this->checkFirewall($ip) )
				{
					return Response::html('<h1>You are not allowed</h1>', 403);
				}
			}
			
			return $handler->handle($request);
		}
		
		/**
		 * Get the client ip.
		 */
		private function getIp( ServerRequestInterface $request ) : string
		{
			if ( $this->ipAttribute !== null )
			{
				return $request->getAttribute($this->ipAttribute);
			}
			else
			{
				$server = $request->getServerParams();
				
				return isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : 'localhost';
			}
		}
		
		/**
		 * [checkFirewalIP description] check firewall compare
		 *
		 * @param  [type] $ip [description]
		 * @return [type]     [description]
		 */
		private function checkFirewalIP( $ip )
		{
			return in_array($ip, $this->whitelist);
		}
	}
