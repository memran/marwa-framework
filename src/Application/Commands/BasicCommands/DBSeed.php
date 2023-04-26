<?php
	
	namespace Marwa\Application\Commands\BasicCommands;
	
	use Exception;
	use Marwa\Application\Commands\AbstractCommand;
	
	class DBSeed extends AbstractCommand {
		
		/**
		 * [$name description]
		 *
		 * @var string
		 */
		var $name = "db:seed {--class}";
		/**
		 * [$description description]
		 *
		 * @var string
		 */
		var $description = "This command will run seeder class";
		/**
		 * [$help description]
		 *
		 * @var string
		 */
		var $help = "Please enter seed class name with arguments i.e db:seed {--class} optional ";
		/**
		 * [$argTitle description]
		 *
		 * @var [type]
		 */
		var $argTitle = [
			'class' => "Please enter seed class name"
		];
		
		/**
		 *
		 */
		public function handle() : void
		{
			$this->info("Starting Seeding...");
			$seed = $this->option('class');
			
			$answer = $this->ask("Are you sure to run seeder class? (y/N) ");
			
			if(!isset($answer) || strtolower($answer) ==='n')
			{
				$this->error('Seeding canceled');
			}
			
			if(strtolower($answer) != 'y')
			{
				$this->error("You need to press 'Y' to confirm this operation.");
			}
			
			if ( empty($seed) )
			{
				$this->runAllSeedClass();
			}
			else
			{
				$this->runSeedClass($seed);
			}
		}
		
		/**
		 *
		 */
		protected function runAllSeedClass()
		{
			$seedFiles = glob($this->getSeederPath() . "*.php");
			if ( empty($seedFiles) )
			{
				$this->error("Nothing to seed");
			}
			else
			{
				if(!is_array($seedFiles))
				{
					$this->error('Seed files is not array.Operation canceled');
				}
				
				foreach ( $seedFiles as $value )
				{
					$this->runSeedClass(basename($value, ".php"));
				}
			}
			
		}
		
		/**
		 * @return string
		 */
		public function getSeederPath()
		{
			return WEBROOT . DS . "database" . DS . "seeds" . DS;
		}
		
		/**
		 * @param $className
		 * @throws Exception
		 */
		protected function runSeedClass( $className )
		{
			try
			{
				$file = $this->getSeederPath() . $className . ".php";
				if ( !file_exists($file) )
				{
					$this->error("Seeder class not found " . $file);
				}
				//$this->dump($file);
				//including file
				include_once($file);
				$obj = new $className;
				
				call_user_func([$obj, 'run']);
				$this->info("Successfully seeding file " . $file);
			} catch ( \Throwable $th )
			{
				throw new Exception($th);
			}
			
		}
	}
