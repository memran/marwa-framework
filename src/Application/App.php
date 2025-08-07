<?php
declare(strict_types=1);

namespace Marwa\Application;

use Marwa\Application\Containers\Container;
use Marwa\Application\Contracts\ContainerInterface;
use Marwa\Application\Debug\Debug;
use Marwa\Application\Exceptions\FileNotFoundException;
use Marwa\Application\Exceptions\InvalidArgumentException;
use Marwa\Application\Facades\Facade;
use Marwa\Application\Routes\Router;
use Marwa\Application\Utils\Filter;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Dotenv\Dotenv;

class App
{

	/**
	 * @var string version
	 */
	const VERSION = "0.1";
	/**
	 * [public description] get globally instance of self object
	 *
	 * @var self object
	 */
	private static $instance;
	/**
	 * @var string
	 */
	protected $_locale = 'en';
	/**
	 * [public description]
	 *
	 * @var [type] ContainerInterface
	 */
	protected $container;

	/**
	 * [public description]
	 *
	 * @var Router
	 */
	//private $router;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * [protected description]
	 *
	 * @var string
	 */
	protected $base_path;

	/**
	 * [protected description]
	 *
	 * @var string
	 */
	protected $environmentFile = '.env';

	/**
	 * [protected description] it is render time
	 *
	 * @var string
	 */
	protected $renderTime;

	/**
	 *  application run from console
	 *
	 * @var boolean
	 */
	protected $console_app = false;

	/**
	 * @var mixed
	 */
	protected $config;
	/**
	 * @var array
	 */
	protected $fallbackLocale = ['en'];

	/**
	 * App constructor.
	 *
	 * @param string|null $path
	 * @param bool $console
	 * @throws FileNotFoundException
	 */
	public function __construct(?string $path = null, bool $console = false)
	{
		//set base path
		if ($path === null) {
			$path = dirname(__FILE__, 4);
		}
		$this->setBasePath($path);

		// app run from console application
		$this->console_app = (bool) $console;

		//set the instance value further retrieval
		static::setInstance($this);

		//bootstrap application
		$this->bootApp();

	}

	/**
	 *
	 * @throws FileNotFoundException
	 */
	public function bootApp(): void
	{
		/**
		 * load Environment File
		 */
		$this->loadEnvironmentFile();

		/**
		 * load facade application
		 */
		$this->loadFacade();

		/**
		 * initialize container
		 */
		$this->setContainer();

		/**
		 * Registering the boot service in the container
		 */
		$this->registerBootService();

		/**
		 * Register All service provider from the configuration
		 */
		$this->registerAllServiceProviders();

		/**
		 * Setup timezone from configuration
		 */
		$this->setTimeZone();

		/**
		 *  If App file called form console command then  disable route setup
		 */
		if (!$this->console_app) {
			$this->setRouter();
		}

	}

	/**
	 * @throws FileNotFoundException
	 * @throws InvalidArgumentException
	 */
	protected function loadEnvironmentFile(): void
	{
		/**
		 * Read environment Files i.e .env
		 */
		//(new Dotenv())->load($this->getEnvironmentFile());
		(new Dotenv())->bootEnv($this->getEnvironmentFile());

		/**
		 * if debug is enabled then Enable Debug option globally
		 */
		if ($this->isDebug()) {
			Debug::getInstance()->enable();
		}
		/**
		 * If Application environment is production, then disable all type error
		 */
		if (strtolower($this->getEnvironment()) === 'production') {
			error_reporting(0);
		}

	}

	/**
	 * @return string
	 * @throws FileNotFoundException
	 * @throws InvalidArgumentException
	 */
	protected function getEnvironmentFile(): string
	{
		/**
		 *  If env is testing then load test env file
		 */
		if (env('APP_ENV') === 'testing') {
			$this->environmentFile = '.env.testing';
		}
		$file = $this->getBasePath() . DIRECTORY_SEPARATOR . $this->environmentFile;
		/**
		 * Check .env file exists if not throw error
		 */
		if (file_exists($file)) {
			return $file;
		} else {
			throw new FileNotFoundException('.env Environment File Not Found!');
		}

	}

	/**
	 * @return string
	 */
	public function getBasePath(): string
	{
		return ($this->base_path) ? $this->base_path : '/';
	}

	/**
	 * @param $path
	 */
	public function setBasePath(string $path): void
	{
		/**
		 * If any / given on the basepath then remove it
		 */
		$this->base_path = rtrim($path, '\/');
	}

	/**
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isDebug(): bool
	{
		if ($this->console_app)
			return false;

		if ((bool) env('APP_DEBUG') || (strtolower($this->getEnvironment()) === 'development')) {
			return true;
		}
	}

	/**
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getEnvironment(): string
	{
		return (string) (env('APP_ENV')) ? env('APP_ENV') : 'development';
	}

	/**
	 *
	 */
	protected function loadFacade(): void
	{
		Facade::setApplication(static::getInstance());
	}

	/**
	 * @return App
	 * @throws FileNotFoundException
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			//$base_path = dirname(__FILE__, 4);
			static::$instance = new static(dirname(__FILE__, 4));
		}

		return static::$instance;
	}

	/**
	 * @param $instance
	 */
	public static function setInstance($instance)
	{
		if (is_null(static::$instance)) {
			static::$instance = $instance;
		}
	}

	/**
	 * [registerBaseService description]
	 *
	 * @return void
	 * @throws FileNotFoundException
	 */
	protected function registerBootService(): void
	{
		/**
		 * Setup All base path to the service container
		 */
		$this->setupAllBasePath();
		/**
		 * Add App::class instance in the container
		 */
		$this->getContainer()->singleton('app', static::getInstance());

		/**
		 * Add ConfigServiceProvider
		 */
		$this->addService("Marwa\Application\ServiceProvider\ConfigServiceProvider");

		/**
		 * setup App config
		 */
		$this->setAppConfig();
		/**
		 * Load rest of base service providers
		 */
		/**
		 * Add RequestService Provider to handle PS7 Request
		 */
		$this->addService("Marwa\Application\ServiceProvider\RequestServiceProvider");
		/**
		 *  Add PSR7 Response Service Provider
		 */
		$this->addService("Marwa\Application\ServiceProvider\ResponseServiceProvider");
		/**
		 *  Add PSR7 Response Emitter
		 */
		$this->addService("Marwa\Application\ServiceProvider\EmitterServiceProvider");
		/**
		 *  Add Session Service Provider
		 */
		$this->addService("Marwa\Application\ServiceProvider\SessionServiceProvider");
		/**
		 * Add Logger Service Provider
		 */
		$this->addService("Marwa\Application\ServiceProvider\LoggerServiceProvider");
	}

	/**
	 *
	 */
	protected function setupAllBasePath()
	{

		$this->getContainer()->singleton('base_path', $this->getBasePath());
		$this->getContainer()->singleton('public_storage', $this->getPublicStoragePath());
		$this->getContainer()->singleton('app_path', $this->getAppPath());
		$this->getContainer()->singleton('private_storage', $this->getPrivateStoragePath());
		$this->getContainer()->singleton('resource_path', $this->getResourcePath());
		$this->getContainer()->singleton('lang_path', $this->getLangPath());
		$this->getContainer()->singleton('config_path', $this->getConfigPath());
		$this->getContainer()->singleton('route_path', $this->getRoutesPath());
		$this->getContainer()->singleton('public_path', $this->getPublicPath());
	}

	/**
	 * @return ContainerInterface
	 */
	public function getContainer(): ContainerInterface
	{
		return $this->container;
	}

	/**
	 *
	 */
	protected function setContainer(): void
	{
		/**
		 *  Get Container Instance
		 */
		if (!isset($this->container))
			$this->container = Container::getInstance();
	
	}

	/**
	 * @return string
	 */
	public function getPublicStoragePath(): string
	{
		return $this->base_path . DIRECTORY_SEPARATOR . 'public/assets/storage';
	}

	/**
	 * @return string
	 */
	public function getAppPath(): string
	{
		return $this->base_path . DIRECTORY_SEPARATOR . 'app';
	}

	/**
	 * @return string
	 */
	public function getPrivateStoragePath(): string
	{
		return $this->base_path . DIRECTORY_SEPARATOR . 'storage';
	}

	/**
	 * @return string
	 */
	public function getResourcePath(): string
	{
		return $this->base_path . DIRECTORY_SEPARATOR . 'resources';
	}

	/**
	 * @return string
	 */
	public function getLangPath(): string
	{
		return $this->getResourcePath() . DIRECTORY_SEPARATOR . 'lang';
	}

	/**
	 * @return string
	 */
	public function getConfigPath(): string
	{
		return $this->base_path . DIRECTORY_SEPARATOR . 'config';
	}

	/**
	 * @return string
	 */
	public function getRoutesPath(): string
	{
		return $this->base_path . DIRECTORY_SEPARATOR . 'routes';
	}

	/**
	 * @return string
	 */
	public function getPublicPath(): string
	{
		return $this->base_path . DIRECTORY_SEPARATOR . 'public';
	}

	/**
	 * @param $service
	 */
	protected function addService($service): void
	{
		$this->getContainer()->addServiceProvider($service);
	}

	/**
	 *
	 */
	protected function setAppConfig(): void
	{
		$this->config = $this->get('config')->file('app.php')->load();
		$this->getContainer()->bind('app_config', $this->config);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key)
	{
		return $this->getContainer()->get($key);
	}

	/**
	 *
	 */
	public function registerAllServiceProviders(): void
	{
		$providers = $this->config()['providers'];
		if (!empty($providers) && is_iterable($providers)) {
			// loop through service providers list
			foreach ($providers as $provider) {
				$this->getContainer()->addServiceProvider($provider);
			}
		}

	}

	/**
	 * @return array
	 */
	public function config(): array
	{
		return $this->get('app_config');
	}

	/**
	 *
	 */
	public function setTimeZone(): void
	{
		$timezone = (string) $this->config()['app']['timezone'];

		if (!is_null($timezone)) {
			if (Filter::isValidTimezone($timezone) == true) {
				ini_set('date.timezone', $timezone);
			}
		}

	}

	/**
	 *
	 */
	public function setRouter(): void
	{
		/**
		 * Add RouteServiceProvide to the service container
		 */
		$this->addService("Marwa\Application\ServiceProvider\RouteServiceProvider");

		/**
		 * Register all middelware to the router
		 */
		$this->registerAllMiddleware();
		/**
		 * Register all defined routes
		 */
		$this->registerRoutes();
	}

	/**
	 *
	 */
	private function registerAllMiddleware(): void
	{
		/**
		 * if middleware key exits on the config array
		 */
		if (array_key_exists('middlewares', $this->config())) {

			/**
			 * if middlewares key in the configuration is not empty and array then
			 *  Loop through it  and register to the router middleware
			 */
			if (!empty($this->config()['middlewares']) && is_array($this->config()['middlewares'])) {
				/**
				 * loop throw the middlewares array
				 */
				foreach ($this->config()['middlewares'] as $k => $value) {
					$this->getRouter()->middleware($value);
				}
			}
		}
	}

	/**
	 * @return mixed
	 */
	public function getRouter()
	{
		/**
		 *  Return RouteServiceProvider from container
		 */
		return $this->get('router');
	}

	/**
	 *
	 */
	protected function registerRoutes(): void
	{
		/**
		 *  Get Router from container and
		 *  Register all the route from the file i.e web.php , api.php
		 */
		$this->getRouter()->registerAllRoutes();
	}

	/**
	 * @return string
	 */
	public function version(): string
	{
		return static::VERSION;
	}

	/**
	 * @return string
	 */
	public function getLocale(): string
	{
		return $this->_locale;
	}

	/**
	 * @param string $lang
	 */
	public function setLocale(string $lang): void
	{
		if (!nullEmpty($lang)) {
			$this->_locale = $lang;
		}
	}

	/**
	 * @return array
	 */
	public function getFallbackLocale(): array
	{

		return $this->fallbackLocale;
	}

	/**
	 * @param array $locale
	 */
	public function setFallbackLocale(array $locale)
	{
		$this->fallbackLocale = $locale;
	}

	/**
	 *
	 */
	public function run(): void
	{
		/**
		 * Emitting the http response
		 */
		$this->emitResponse();
	}

	/**
	 *
	 */
	protected function emitResponse(): void
	{
		$this->response = $this->getResponse()
			->withHeader('X-Response-Time', $this->getRenderTime());

		/**
		 * Cleaning the header
		 */

		if (ob_get_level()) {
			ob_end_clean();
		}
		/**
		 *  Throw empty response
		 */
		if (empty($this->response)) {
			$this->get('emitter')->emit(Response::empty());
		} else {
			$this->get('emitter')->emit($this->response);
		}
		/**
		 * finally die whatever!
		 */
		die();
	}

	/**
	 * @return ResponseInterface
	 */
	public function getResponse(): ResponseInterface
	{
		$response = $this->getRouter()->getResponse();
		$this->calcRenderTime();

		return $response;
	}

	/**
	 *
	 */
	public function calcRenderTime(): void
	{
		$start = START_APP;
		$end = microtime(true);
		//$renderTime = ($end - $start);
		$this->renderTime = sprintf("%.6f", ($end - $start));
	}

	/**
	 * @return string
	 */
	public function getRenderTime(): string
	{
		if (is_null($this->renderTime)) {
			$this->calcRenderTime();
		}

		return $this->renderTime;
	}

}
