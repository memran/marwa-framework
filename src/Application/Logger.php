<?php
	declare( strict_types = 1 );
	
	namespace Marwa\Application;
	
	use Exception;
	use Psr\Log\LoggerInterface;
	use Psr\Log\LogLevel;
	use SimpleLog\Logger as SimpleLog;
	
	class Logger {
		
		/**
		 * @var
		 */
		private static $__instance;
		/**
		 * [public description]
		 *
		 * @var logger
		 */
		protected $_logger;
		/**
		 * [protected description]
		 *
		 * @var Config
		 */
		private $config;
		/**
		 * [protected description]  log file name
		 *
		 * @var string
		 */
		private $log_file = 'app.log';
		/**
		 * [protected description] log channel name
		 *
		 * @var string
		 */
		private $log_channel = 'MarwaApp';
		/**
		 * [protected description] default log level
		 *
		 * @var [type]
		 */
		private $log_level = 'debug';
		
		/**
		 * [__construct description] logger constructor
		 * @throws Exception
		 */
		private function __construct()
		{
			/**
			 *  Read Logger configuration from app.php
			 */
			$this->setLoggerConfig();
			/**
			 *  Setup Logger variables
			 */
			$this->setupLogger();
			
			/**
			 * create PSR-3 logger class
			 */
			$this->createLogger();
		}
		
		/**
		 *
		 */
		protected function setLoggerConfig() : void
		{
			$this->config = config();
		}
		
		/**
		 *
		 */
		protected function setupLogger() : void
		{
			$this->setLogFileName((string) $this->getLoggerConfig('log_file'));
			$this->setLogChannel((string) $this->getLoggerConfig('log_channel'));
			$this->setDefaultLogLevel((string) $this->getLoggerConfig('log_level'));
		}
		
		/**
		 * @param string $logFileName
		 */
		protected function setLogFileName( string $logFileName ) : void
		{
			$this->log_file = $logFileName;
		}
		
		/**
		 * @param string|null $key
		 * @return mixed
		 */
		protected function getLoggerConfig( string $key = null )
		{
			if ( is_null($key) )
			{
				return $this->config['app'];
			}
			
			return $this->config['app'][ $key ];
		}
		
		/**
		 * @param string $level
		 */
		protected function setDefaultLogLevel( string $level ) : void
		{
			$this->log_level = $level;
		}
		
		/**
		 * @throws Exception
		 */
		protected function createLogger()
		{
			try
			{
				$log = new SimpleLog(
					$this->getLogStorage(),
					$this->getLogChannel()
				);
				$this->setLogger($log);
				$this->setLoggerLogLevel($this->getDefaultLogLevel());
			} catch ( Exception $e )
			{
				throw new Exception($e);
			}
		}
		
		/**
		 * @return string
		 */
		protected function getLogStorage() : string
		{
			$logFile=app('private_storage') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $this->getLogFileName();
			//check if file exists
			if(file_exists($logFile)){
				return $logFile;
			}else{
				try 
				{
				    $old = umask(0); 
				    mkdir($logFile, 0777, true) ;
				    umask($old); 
				    return $logFile;
			        } 
				catch ( Exception $e )
				{
					throw new Exception($e);
				}
				
				
			}
		}
		
		/**
		 * @return string
		 */
		protected function getLogFileName() : string
		{
			return $this->log_file;
		}
		
		/**
		 * @return string
		 */
		protected function getLogChannel() : string
		{
			return $this->log_channel;
		}
		
		/**
		 * @param string $channel
		 */
		protected function setLogChannel( string $channel ) : void
		{
			$this->log_channel = $channel;
		}
		
		/**
		 * @param $level
		 */
		protected function setLoggerLogLevel( $level ) : void
		{
			
			switch ( $level )
			{
				case 'error' :
					$this->getLogger()->setLogLevel(LogLevel::ERROR);
					break;
				case 'notice':
					$this->getLogger()->setLogLevel(LogLevel::NOTICE);
					break;
				case 'debug' :
					$this->getLogger()->setLogLevel(LogLevel::DEBUG);
					break;
				case 'alert':
					$this->getLogger()->setLogLevel(LogLevel::ALERT);
					break;
				case 'critical':
					$this->getLogger()->setLogLevel(LogLevel::CRITICAL);
					break;
				case 'emergency':
					$this->getLogger()->setLogLevel(LogLevel::EMERGENCY);
					break;
				default:
					$this->getLogger()->setLogLevel(LogLevel::INFO);
			}
		}
		
		/**
		 * @return Logger|LoggerInterface
		 */
		protected function getLogger()
		{
			return $this->_logger;
		}
		
		/**
		 * @param LoggerInterface $log
		 * @throws Exception
		 */
		protected function setLogger( LoggerInterface $log )
		{
			if ( !$log instanceof LoggerInterface )
			{
				throw new Exception('Logger Class is not instance of LoggerInterface');
			}
			$this->_logger = $log;
		}
		
		/**
		 * @return string
		 */
		protected function getDefaultLogLevel() : string
		{
			return $this->log_level;
		}
		
		/**
		 * @return Logger
		 */
		public static function getInstance()
		{
			if ( static::$__instance == null )
			{
				static::$__instance = new Logger();
			}
			
			return static::$__instance;
		}
		
		/**
		 * @param $msg
		 * @param array $params
		 * @param string $level
		 */
		public function log( $msg, $params = [], $level = 'info' ) : void
		{
			$this->getLogger()->log($level, $msg, $params);
		}
		
		/**
		 * @param $name
		 * @param $arguments
		 * @return mixed
		 */
		public function __call( $name, $arguments )
		{
			return call_user_func_array([$this->getLogger(), $name], $arguments);
		}
	}
