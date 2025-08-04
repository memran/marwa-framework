<?php

use Carbon\Carbon;
use Marwa\Application\App;
use Marwa\Application\Exceptions\FileNotFoundException;
use Marwa\Application\Exceptions\InvalidArgumentException;
use Marwa\Application\Response;
use Marwa\Application\Security\Bcrypt;
use Marwa\Application\Utils\
{
	Collection,
	Filter,
	Hash,
	Random,
	Validate
};
use Psr\Http\Message\ResponseInterface;


if (!function_exists('env')) {
	/**
	 * @param $key
	 * @param null $default
	 * @return string|mixed
	 * @throws InvalidArgumentException
	 */
	function env($key, ?string $default = null): string
	{
		if (is_null($key)) {
			throw new InvalidArgumentException("Invalid Key");
		}

		//check if default value is not null
		if (!is_null($default) && is_null(getenv($key))) {
			return $default;
		}

		return getenv($key);
	}
}


if (!function_exists('base_url')) {
	/**
	 * @return string
	 * @throws InvalidArgumentException
	 */
	function base_url()
	{
		return rtrim(env('APP_URL'), '/') . '/';
	}
}


if (!function_exists('config')) {
	/**
	 * @param null $key
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	function config($key = null)
	{
		return (is_null($key)) ? app('app_config') : app('app_config')[$key];
	}
}

if (!function_exists('AppConfig')) {
	/**
	 * @param null $key
	 * @return array|int|mixed|object|string
	 */
	function AppConfig(?string $key = null)
	{
		return (is_null($key)) ? Hash::from(app('app_config')) : app('app_config')[$key];
	}
}

if (!function_exists('logger')) {
	/**
	 * @param $msg
	 * @param array $params
	 * @param string $level
	 * @throws FileNotFoundException
	 */
	function logger(string $msg, array $params = [], string $level = 'debug')
	{
		app('logger')->log($msg, $params, $level);
	}
}

if (!function_exists('debug')) {
	/**
	 * @param $msg
	 * @param array $params
	 * @throws FileNotFoundException
	 */
	function debug($msg, $params = [])
	{
		logger($msg, $params, 'debug');
	}
}


if (!function_exists('cache')) {
	/**
	 * @param string|null $key
	 * @param string|null $value
	 * @param string $driver
	 * @param int $expire
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	function cache(?string $key = null, ?string $value = null, string $driver = 'file', int $expire = 0)
	{
		if (is_null($key)) {
			return app('cache');
		}

		if (!is_null($value)) {
			app('cache')->disk($driver)->set($key, $value, $expire);
		} else {
			return app("cache")->disk($driver)->get($key);
		}
	}
}


if (!function_exists('app')) {
	/**
	 * @param null $abstract
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	function app(?string $abstract = null)
	{
		if (is_null($abstract)) {
			return App::getInstance()->get('app');
		}

		return App::getInstance()->get($abstract);
	}
}


if (!function_exists('container')) {
	/**
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	function container()
	{
		return app()->getContainer();
	}
}


if (!function_exists('view')) {
	/**
	 * @param string $template
	 * @param array $data
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	function view(string $template, array $data = [])
	{
		return app('view')->render($template, $data);
	}
}

if (!function_exists('base_path')) {
	/**
	 * @param null $path
	 * @return mixed|string
	 * @throws FileNotFoundException
	 */
	function base_path(?string $path = null)
	{
		return (!is_null($path)) ? app('base_path') . DIRECTORY_SEPARATOR . $path : app('base_path');
	}
}

if (!function_exists('config_path')) {
	/**
	 * @return string
	 * @throws FileNotFoundException
	 */
	function config_path(): string
	{
		return app('config_path') . DIRECTORY_SEPARATOR;
	}
}

if (!function_exists('storage_path')) {
	/**
	 * @param null $path
	 * @return mixed|string
	 * @throws FileNotFoundException
	 */
	function storage_path(?string $path = null)
	{
		return (!is_null($path)) ? app('public_storage') . DIRECTORY_SEPARATOR . $path : app('public_storage');
	}
}

if (!function_exists('private_storage')) {
	/**
	 * @param null $path
	 * @return mixed|string
	 * @throws FileNotFoundException
	 */
	function private_storage(?string $path = null)
	{
		return (!is_null($path)) ? app('private_storage') . DIRECTORY_SEPARATOR . $path : app('private_storage');
	}
}

if (!function_exists('upload')) {
	/**
	 * @param $file
	 * @param null $uploadFilename
	 * @param null $extension
	 * @return string
	 * @throws FileNotFoundException
	 */
	function upload(string $file, ?string $uploadFilename = null, ?string $extension = null)
	{
		//$filename = null;
		if (is_null($extension)) {
			$extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
		}

		if (is_null($uploadFilename)) {
			$uploadFilename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
		}

		$filename = sprintf('%s.%0.8s', $uploadFilename, $extension);
		$file->moveTo(storage_path($filename));
		return $filename;
	}
}


if (!function_exists('asset')) {
	/**
	 * @param string $uri
	 * @return string
	 * @throws InvalidArgumentException
	 */
	function asset(string $uri)
	{
		return base_url() . "assets/" . $uri;
	}
}

if (!function_exists('secret')) {
	/**
	 * @param int $length
	 * @param bool $special_chars
	 * @param bool $extra_special_chars
	 * @return string
	 */
	function secret(int $length = 12, $special_chars = true, $extra_special_chars = false): string
	{
		$charlist = '0-9a-zA-Z';

		if ($special_chars) {
			$charlist .= '!@#$%^&*()';
		}

		if ($extra_special_chars) {
			$charlist .= '-_ []{}<>~`+=,.;:/?|\'';
		}

		return generate($length, $charlist);
	}
}

if (!function_exists('generate')) {
	/**
	 * @param int $length
	 * @param string $charlist
	 * @return string
	 */
	function generate(int $length = 10, string $charlist = '0-9a-zA-Z')
	{
		return Random::generate($length, $charlist);
	}
}
/**
 * it will dump msg from kint library and die the page
 *
 * @param array/string/int
 * @return void
 */

if (!function_exists('dd')) {
	/**
	 * @param $msg
	 */
	function dd($msg)
	{
		d($msg);
		die();
	}
}


if (!function_exists('session')) {
	/**
	 * @param $key
	 * @param null $value
	 * @param null $lifetime
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	function session($key, ?string $value = null, ?int $lifetime = null)
	{
		$session = app('session'); //get session object

		/**
		 * if value is null then retrieve session value by provided key
		 */
		if (is_null($value)) {
			return $session->get($key);
		}
		/**
		 * if expire time is not null then set expire time
		 */
		if (!is_null($lifetime)) {
			$session->expire($lifetime);
		}
		$session->set($key, $value);
	}

}

if (!function_exists('csrfToken')) {
	/**
	 * @return string
	 * @throws FileNotFoundException
	 */
	function csrfToken(): string
	{
		return app('session')->csrfTokenValue();
	}
}

if (!function_exists('flash')) {
	/**
	 * @param $key
	 * @param null $value
	 * @return string
	 * @throws FileNotFoundException
	 */
	function flash($key, $value = null)
	{
		$session = app('session');
		if (is_null($value)) {
			return $session->getFlash($key);
		}
		$session->setFlash($key, $value);
	}
}

if (!function_exists('setMessage')) {
	/**
	 * @param string $level
	 * @param string $key
	 * @param $msg
	 * @throws Exception
	 */
	function setMessage(string $level, string $key, mixed $msg): void
	{
		if (is_null($level) || is_null($level) || is_null($msg)) {
			throw new Exception('All value is required');
		}
		$levelArray = [
			'error',
			'success',
			'warning',
			'info'
		];
		if (!in_array($level, $levelArray)) {
			throw new Exception("{$level} is not correct value");
		}
		$session = app('session');
		if (is_array($msg)) {

			$session->setFlash($key, [$level => $msg]);
		} else {
			$session->setFlash($key, $level . '|' . $msg);
		}

	}
}


if (!function_exists('getMessage')) {
	/**
	 * @param string $key
	 * @return array
	 * @throws FileNotFoundException
	 */
	function getMessage(string $key): array
	{
		$session = app('session');
		$msg = $session->getFlash($key);
		if (!Validate::isNone($msg)) {

			if (is_array($msg)) {
				return $msg;
			}
			if (is_string($msg)) {
				return explode('|', $msg);
			}
		}

		return [];
	}
}

if (!function_exists('notice')) {
	/**
	 * @param $key
	 * @param bool $dismissible
	 * @return string
	 * @throws FileNotFoundException
	 */
	function notice($key, $dismissible = false): string
	{
		$levelArray = [
			'error' => 'alert-danger',
			'success' => 'alert-success',
			'warning' => 'alert-warning',
			'info' => 'alert-info'
		];
		//read the flash message
		$msg = getMessage($key);

		if (empty($msg)) {
			return '';
		}

		//dismiss button enable
		if ($dismissible) {
			$template = '<div class="alert {level} alert-dismissible fade show" role="alert">
                {msg}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>';
		} else {
			$template = '<div class="alert {level}" role="alert">{msg}</div>';
		}
		//dd($msg);
		if (is_array($msg)) {
			$notice_msg = '';
			foreach ($msg as $level => $value) {
				if (is_array($value)) {
					foreach ($value as $msg) {
						$msgTemp = str_replace('{level}', $levelArray[$level], $template);
						$notice_msg .= str_replace('{msg}', $msg, $msgTemp);
					}
				} else {
					[$level, $message] = $msg;
					$notice_msg = str_replace('{level}', $levelArray[$level], $template);
					$notice_msg = str_replace('{msg}', $message, $notice_msg);

					return $notice_msg;
				}

			}

			return $notice_msg;
		}
	}
}

if (!function_exists('redirect')) {
	/**
	 * @param string $url
	 * @param int $status
	 * @return ResponseInterface
	 * @throws InvalidArgumentException
	 */
	function redirect(string $url = '', int $status = 301)
	{
		return Response::redirect(base_url() . Filter::sanitize($url), $status);
	}
}

if (!function_exists('lang')) {
	/**
	 * @param null $key
	 * @return string
	 * @throws FileNotFoundException
	 */
	function lang($key = null): string
	{
		if (is_null($key)) {
			return app('translator');
		}

		return app('translator')->trans($key);
	}
}

if (!function_exists('now')) {
	/**
	 * @param null $options
	 * @return Carbon
	 */
	function now($options = null)
	{
		return Carbon::now($options);
	}
}

if (!function_exists('today')) {
	/**
	 * @return string
	 */
	function today(): string
	{
		return Carbon::today();
	}
}

if (!function_exists('sec2min')) {
	/**
	 * @param $seconds
	 * @return string
	 */
	function sec2min($seconds): string
	{
		return sprintf("%02.2d:%02.2d", floor($seconds / 60), $seconds % 60);
	}
}

if (!function_exists('is_multi')) {
	/**
	 * @param $arr
	 * @return bool
	 */
	function is_multi($arr): bool
	{
		if (!is_array($arr)) {
			return false;
		}

		$rv = array_filter($arr, 'is_array');
		if (count($rv) > 0) {
			return true;
		}

		return false;
	}
}

if (!function_exists('toArray')) {
	/**
	 * @param $data
	 * @return array
	 */
	function toArray($data): array
	{
		if (!is_null($data)) {
			$res = json_decode(json_encode($data), true);

			return $res;
		}

		return $data;
	}
}

if (!function_exists('nullEmpty')) {
	/**
	 * @param  $str
	 * @return bool
	 */
	function nullEmpty($str)
	{
		if (is_string($str)) {
			return (!isset($str) || trim($str) == '');
		}

		return empty($str) || !isset($str);
	}
}

if (!function_exists('collect')) {
	/**
	 * @param array $data
	 * @return Collection
	 */
	function collect(array $data = [])
	{
		return new Collection($data);
	}
}

if (!function_exists('bcrypt')) {
	/**
	 * @param string|null $secret
	 * @return Bcrypt|string
	 */
	function bcrypt(?string $secret = null)
	{
		$crypto = new Bcrypt();
		if (isset($secret)) {
			return $crypto->create($secret);
		}

		return $crypto;
	}
}

if (!function_exists('current_url')) {
	/**
	 * @return mixed|string
	 */
	function current_url()
	{
		$url = explode('?', $_SERVER["REQUEST_URI"]);
		return ltrim(reset($url), '/');

	}
}

if (!function_exists('abort_404')) {
	/**
	 * @param string $message
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	function abort_404(string $message)
	{
		return view('404', ['title' => $message, 'message' => $message]);
	}
}

if (!function_exists('abort')) {
	/**
	 * @param string $message
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	function abort(string $message)
	{
		return view('500', ['title' => $message, 'message' => $message]);
	}
}