<?php

declare(strict_types=1);
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */

namespace Marwa\Application\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class HttpClient
{
    /**
     * [$client description]
     *
     * @var null
     */
    var $client = null;

    /**
     * [$multipart description]
     *
     * @var array
     */
    var $multipart = [];
    /**
     * [$headers description]
     *
     * @var array
     */
    var $headers = [];

    /**
     * [$timeout description]
     *
     * @var integer
     */
    var $timeout = 3;

    /**
     * [__construct description]
     */
    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $args
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function send(string $method, string $url, array $args = [])
    {
        $args['timeout'] = $this->timeout;

        if (!empty($this->multipart)) {
            $args['multipart'] = $this->multipart;
        }
        if (!empty($this->headers)) {
            $args['headers'] = $this->headers;
        }

        try {
            return $this->client->request($method, $url, $args);
        } catch (RequestException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param string $url
     * @param array $params
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function get(string $url, array $params = [])
    {
        return $this->send('GET', $url, $params);
    }

    /**
     * @param string $url
     * @param array $params
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function post(string $url, array $params = [])
    {
        return $this->send('POST', $url, $params);
    }

    /**
     * @return Client|null
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $field
     * @param $file
     * @param string $filename
     * @return $this
     */
    public function attach($field, $file, $filename = '')
    {
        $attachment = [
            'name' => $field,
            'contents' => $file,
            'filename' => $filename
        ];
        array_push($this->multipart, $attachment);
        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function withHeaders(array $options)
    {
        array_push($this->headers, $options);
        return $this;
    }

    /**
     * @param  int $sec
     * @return $this
     */
    public function timeout(int $sec = 3)
    {
        $this->timeout = $sec;
        return $this;
    }
}
