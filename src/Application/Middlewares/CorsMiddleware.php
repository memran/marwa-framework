<?php

namespace Marwa\Application\Middlewares;

use Marwa\Application\Exceptions\InvalidArgumentException;
use Marwa\Application\Response;
use Marwa\Application\Utils\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class CorsMiddleware implements MiddlewareInterface
{

	private $allowed_hosts = []; //Access-Control-Allow-Origin
	private $allowed_host;
	private $origin_host;
	private $options = [
		'methods' => 'GET,POST,PUT,DELETE,PATCH,OPTIONS',
		'headers' => 'X-Requested-With,Content-Type,Accept,Origin,Authorization'
	];

	/**
	 * {@inheritdoc}
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		//response pre-flight
		if ($request->hasHeader('Origin') ) {
				$this->preProcessConfiguration();
				if($request->getMethod() == "OPTIONS"){
					return $this->preFlightRequest($request);
				}
				// }else {
				// 	return $this->handleAndResponse($request,$handler);
				// }
			return $this->handleAndResponse($request,$handler);
		}

		return $handler->handle($request);

	}

	protected function preProcessConfiguration()
	{
		//read the environment
		$this->readEnvHeaders();
	    //set the origin host
		$this->setOriginHost($request);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	protected function readEnvHeaders()
	{
		$hosts = (string) env('CORS_ALLOWED_HOSTS');

		if (!empty($hosts)) {
			$this->allowed_hosts = explode(',', $hosts);
		}
		if (!empty(env('CORS_METHOD'))) {
			$this->options['methods'] = (string) env('CORS_METHOD');
		}
		if (!empty(env('CORS_HEADER'))) {
			$this->options['headers'] = (string) env('CORS_HEADER');
		}

	}

	/**
	 * @param $req
	 */
	protected function setOriginHost($req)
	{
		 if ($req->hasHeader('Origin')) {
			$this->origin_host = $req->getHeader('Origin')[0];
		}
	}
	/**
	 * @return mixed
	 */
	protected function getOriginHost()
	{
		$origin = parse_url($this->origin_host);
		if (is_array($origin)) {
			return $origin;
		}

		return false;

	}
	/**
	 * @param $req
	 * @return bool
	 */
	protected function checkIsSameHost($req)
	{
		$host = $req->getHeader('Host')[0];
		$origin = $this->getOriginHost();
		if (is_null($origin) || !isset($origin['host'])) {
			return false;
		}

		if ($host === $origin['host']) {
			$this->setAllowedHost($this->origin_host);
			return true;
		}

		return false;

	}

	/**
	 * [setAllowedHost description]
	 *
	 * @param [type] $host [description]
	 */
	protected function setAllowedHost($host)
	{
		$this->allowed_host = $host;
		// logger("Allowed Host:".$this->allowed_host);
	}

/**
 * handleAndResponse of CORS Request
 * */
	protected function handleAndResponse($request, $handler)
	{
		if (!$this->checkIsSameHost($request)) {
			if (!$this->checkIsCrossOrigin($request)) //check if it is cross origin request and allowed
			{
				return $handler->handle($request);
			}
		}
		 $response = $handler->handle($request);

		return $response->withHeader('Access-Control-Allow-Origin',$this->allowed_host);
		//->withAddedHeader('Access-Control-Allow-Credentials','true')
		//->withAddedHeader('Access-Control-Max-Age',86400);
	}

	/**
	 * @param $req
	 * @return bool
	 */
	protected function checkIsCrossOrigin($req)
	{
		$origin = $this->getOriginHost();
		if (Arr::empty($this->allowed_hosts)) {
			return true;
		}
		foreach ($this->allowed_hosts as $key) {
			//if allowed host is all set
			if ($key === "*") {
				$this->setAllowedHost($key);

				return true;
				break;
			}

			//parse allowed hosts
			$host = parse_url($key);
			// code...
			// compare allowed host and origin host
			if ($host['host'] === $origin['host']) {
				//if origin host has different port
				if (array_key_exists('port', $origin)) {
					//if host port is empty
					if (!array_key_exists('port', $host)) {
						$host['port'] = 80;
					}

					if ((int) $origin['port'] === $host['port']) {
						$this->setAllowedHost($this->origin_host);

						return true;
						break;
					} else {
						return false;
						break;
					}
				} else {
					$this->setAllowedHost($this->origin_host);

					return true;
					break;
				}
			}

		}

	}

	/**
	 * @param $request
	 * @return ResponseInterface
	 * @throws InvalidArgumentException
	 */
	protected function preFlightRequest($request)
	{
		if (!$this->checkIsSameHost($request)) {
			//return $this->responsePreFlight();
			if (!$this->checkIsCrossOrigin($request)) //check if it is cross origin request and allowed
			{
				return Response::empty();
			}
		}
		return $this->responsePreFlight();

	}

	/**
	 * @return ResponseInterface
	 */
	protected function responsePreFlight()
	{
		if ($this->options['headers'] == null) {
			$this->options['headers'] = 'X-Requested-With,Content-Type,Accept,Origin,Authorization';
		}
		if ($this->options['methods'] == null) {
			$this->options['methods'] = 'GET,POST,PUT,DELETE,PATCH,OPTIONS';
		}
		$headers = [
			'Access-Control-Allow-Origin' => $this->allowed_host,
			'Access-Control-Allow-Headers' => $this->options['headers'],
			'Access-Control-Allow-Methods' => $this->options['methods'],
			'Access-Control-Allow-Credentials'=> 'true',
			'Access-Control-Max-Age'=> 86400
		];

		return Response::empty($headers);
	}


}
