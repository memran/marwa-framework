<?php
	/**
	 * @author    Mohammad Emran <memran.dhk@gmail.com>
	 * @copyright 2018
	 *
	 * @see https://www.github.com/memran
	 * @see http://www.memran.me
	 */
	
	namespace Marwa\Application\Console;
	
	use Exception;
	use Marwa\Application\Commands\ConsoleCommandTrait;
	use Symfony\Component\Console\Application;
	
	class ConsoleApp {
		
		use ConsoleCommandTrait;
		
		/**
		 * @var Application
		 */
		var $console = null;
		
		/**
		 * @var string
		 */
		var $path = null;
		
		/**
		 * ConsoleApp constructor.
		 * @param string $path
		 */
		public function __construct( string $path )
		{
			if ( is_null($path) )
			{
				throw new Exception("Path is empty", $path);
			}
			//setting path
			$this->path = $path;
			
			//cal console application
			$this->console = new Application("Marwa Console Tool");
			
			//register all commands
			$this->registerCommands();
		}
		
		/**
		 * @register all command
		 */
		public function registerCommands()
		{
			//loading basic commands
			$this->loadSystemCommands();
			//load user defined commands
			$this->loadUserCommands();
		}
		
		
		/**
		 * [loadSystemCommands description]
		 *
		 * @return [type] [description]
		 */
		public function loadSystemCommands()
		{
			$path = $this->getCommandPath() . DS . 'BasicCommands' . DS . '*.php';
			$files = $this->readFileNames($path);
			if ( !$files )
			{
				return false;
			}
			
			foreach ( $files as $file )
			{
				$cmd = '\Marwa\Application\Commands\BasicCommands\\' . $file;
				$this->console->add(new $cmd());
			}
		}
		
		/**
		 * [readFileNames description]
		 *
		 * @param  [type] $path [description]
		 * @param string $omit [description]
		 * @return [type]       [description]
		 */
		protected function readFileNames( $path, $omit = ".php" )
		{
			$files = glob($path); //read command files automatically
			if ( empty($files) )
			{
				return false;
			}
			$listFiles = [];
			foreach ( $files as $file )
			{
				$cmdName = basename($file, $omit); //read base file name without extension
				array_push($listFiles, $cmdName);
			}
			
			return $listFiles;
		}
		
		/**
		 * [load description]
		 *
		 * @return [type] [description]
		 */
		public function loadUserCommands()
		{
			$path = $this->path . DS . 'app' . DS . 'Commands' . DS . '*.php';
			$files = $this->readFileNames($path);
			if ( empty($files) )
			{
				return false;
			}
			
			foreach ( $files as $file )
			{
				$cmd = '\App\Commands\\' . $file;
				$this->console->add(new $cmd());
			}
		}
		
		/**
		 *  run the command
		 */
		public function run()
		{
			try
			{
				$this->console->run();
				
			} catch ( \Throwable $e )
			{
				logger("Failed to run console application");
			}
			
		}
	}
