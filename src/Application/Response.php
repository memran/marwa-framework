<?php
declare(strict_types=1);

namespace Marwa\Application;

use Laminas\Diactoros\Response\
{
	EmptyResponse,
	HtmlResponse,
	JsonResponse,
	RedirectResponse,
	TextResponse,
	XmlResponse
};
use Laminas\Diactoros\Response as ZendResponse;
use Psr\Http\Message\ResponseInterface;

class Response
{

	/**
	 * [getInstance description]
	 *
	 * @return ResponseInterface [description]
	 */
	public static function getInstance(): ResponseInterface
	{
		return new ZendResponse();
	}


	/**
	 * @param string $content
	 * @param int $status
	 * @param array $headers
	 * @return HtmlResponse
	 */
	public static function html(string $content, int $status = 200, array $headers = []): HtmlResponse
	{
		return new HtmlResponse($content, $status, $headers);
	}

	/**
	 * [text description] text response
	 *
	 * @param string $content [description]
	 * @param integer $status [description]
	 * @param array $headers [description]
	 * @return ResponseInterface          [description]
	 */
	public static function text(string $content, int $status = 200, array $headers = []): ResponseInterface
	{
		return new TextResponse($content, $status, $headers);

	}

	/**
	 * [xml description] xml response
	 *
	 * @param string $content [description]
	 * @param integer $status [description]
	 * @param array $headers [description]
	 * @return ResponseInterface          [description]
	 */
	public static function xml(string $content, int $status = 200, array $headers = []): ResponseInterface
	{
		return new XmlResponse($content, $status, $headers);
	}

	/**
	 * [json description] json response
	 *
	 * @param string $content [description]
	 * @param integer $status [description]
	 * @param array $headers [description]
	 * @return ResponseInterface          [description]
	 */
	public static function json($content, int $status = 200, array $headers = []): ResponseInterface
	{
		return new JsonResponse($content, $status, $headers);
	}

	/**
	 * @param string $url
	 * @param int $status
	 * @param array $headers
	 * @return ResponseInterface
	 */
	public static function redirect(string $url, int $status = 301, array $headers = []): ResponseInterface
	{
		return new RedirectResponse($url, $status, $headers);

	}

	/**
	 * @param array $headers
	 * @return ResponseInterface
	 */
	public static function empty(array $headers = []): ResponseInterface
	{
		return new EmptyResponse(200, $headers);
	}

	/**
	 * [error description] error response
	 *
	 * @param string $content [description]
	 * @param integer $status [description]
	 * @param array $headers [description]
	 * @return ResponseInterface          [description]
	 */
	public static function error(string $content, int $status = 503, array $headers = []): ResponseInterface
	{
		return new HtmlResponse($content, $status, $headers);
	}

}