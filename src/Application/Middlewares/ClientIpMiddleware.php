<?php
	
	namespace Marwa\Application\Middlewares;
	
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	
	class ClientIpMiddleware implements MiddlewareInterface {
		
		/**
		 * @var bool
		 */
		private $remote = false;
		
		/**
		 * @var string The attribute name
		 */
		private $attribute = 'client-ip';
		
		/**
		 * @var array The trusted proxy headers
		 */
		private $proxyHeaders = [];
		
		/**
		 * @var array The trusted proxy ips
		 */
		private $proxyIps = [];
		
		/**
		 * Configure the proxy.
		 * @param array $ips
		 * @param array $headers
		 * @return ClientIpMiddleware
		 */
		public function proxy(
			array $ips = [],
			array $headers = [
				'Forwarded',
				'Forwarded-For',
				'X-Forwarded',
				'X-Forwarded-For',
				'X-Cluster-Client-Ip',
				'Client-Ip',
			]
		)
		{
			$this->proxyIps = $ips;
			$this->proxyHeaders = $headers;
			
			return $this;
		}
		
		/**
		 * @param bool $remote
		 * @return $this
		 */
		public function remote( bool $remote = true ) : self
		{
			$this->remote = $remote;
			
			return $this;
		}
		
		/**
		 * @param string $attribute
		 * @return $this
		 */
		public function attribute( string $attribute ) : self
		{
			$this->attribute = $attribute;
			
			return $this;
		}
		
		/**
		 * @param ServerRequestInterface $request
		 * @param RequestHandlerInterface $handler
		 * @return ResponseInterface
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			$ip = $this->getIp($request);
			
			return $handler->handle($request->withAttribute($this->attribute, $ip));
		}
		
		/**
		 * @param ServerRequestInterface $request
		 * @return string|null
		 */
		private function getIp( ServerRequestInterface $request )
		{
			$localIp = $this->getLocalIp($request);
			if ( $this->proxyIps && !in_array($localIp, $this->proxyIps) )
			{
				// Local IP address does not point at a known proxy, do not attempt
				// to read proxied IP address.
				return $localIp;
			}
			$proxiedIp = $this->getProxiedIp($request);
			
			if ( !empty($proxiedIp) )
			{
				// Found IP address via proxy-defined headers.
				return $proxiedIp;
			}
			
			$remoteIp = $this->getRemoteIp();
			if ( !empty($remoteIp) )
			{
				// Found IP address via remote service.
				return $remoteIp;
			}
			
			return $localIp;
		}
		
		/**
		 * @param ServerRequestInterface $request
		 * @return mixed
		 */
		private function getLocalIp( ServerRequestInterface $request )
		{
			$server = $request->getServerParams();
			
			if ( !empty($server['REMOTE_ADDR']) && self::isValid($server['REMOTE_ADDR']) )
			{
				return $server['REMOTE_ADDR'];
			}
		}
		
		/**
		 * @param string $ip
		 * @return bool
		 */
		private static function isValid( string $ip ) : bool
		{
			return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false;
		}
		
		/**
		 * @param ServerRequestInterface $request
		 * @return string
		 */
		private function getProxiedIp( ServerRequestInterface $request )
		{
			foreach ( $this->proxyHeaders as $name )
			{
				if ( $request->hasHeader($name) )
				{
					if ( substr($name, -9) === 'Forwarded' )
					{
						$ip = $this->getForwardedHeaderIp($request->getHeaderLine($name));
					}
					else
					{
						$ip = $this->getHeaderIp($request->getHeaderLine($name));
					}
					
					if ( $ip !== null )
					{
						return $ip;
					}
				}
			}
		}
		
		/**
		 * @param string $header
		 * @return string
		 */
		private function getForwardedHeaderIp( string $header )
		{
			foreach ( array_reverse(array_map('trim', explode(',', strtolower($header)))) as $values )
			{
				foreach ( array_reverse(array_map('trim', explode(';', $values))) as $directive )
				{
					if ( strpos($directive, 'for=') !== 0 )
					{
						continue;
					}
					
					$ip = trim(substr($directive, 4));
					
					if ( self::isValid($ip) && !in_array($ip, $this->proxyIps) )
					{
						return $ip;
					}
				}
			}
		}
		
		/**
		 * @param string $header
		 * @return mixed
		 */
		private function getHeaderIp( string $header )
		{
			foreach ( array_reverse(array_map('trim', explode(',', $header))) as $ip )
			{
				if ( self::isValid($ip) && !in_array($ip, $this->proxyIps) )
				{
					return $ip;
				}
			}
		}
		
		/**
		 * @return false|string
		 */
		private function getRemoteIp()
		{
			if ( $this->remote )
			{
				$ip = file_get_contents('https://ipecho.net/plain');
				if ( self::isValid($ip) )
				{
					return $ip;
				}
			}
		}
	}
