<?php
	declare( strict_types = 1 );
	
	
	namespace Marwa\Application\Jobs;
	
	use Carbon\Carbon;
	use Marwa\Application\Facades\DB;
	use Symfony\Component\Process\Exception\ProcessFailedException;
	use Symfony\Component\Process\Process;
	
	
	class ScheduleWorker {
		
		use JobTrait;
		
		/**
		 * @var array
		 */
		var $tasks = [];
		/**
		 * @var array[]
		 */
		var $initial_task = [
			'1M' => [],
			'5M' => [],
			'10M' => [],
			'15M' => [],
			'30M' => [],
			'1H' => [],
			'1D' => [],
			'1D2' => [],
			'1W' => [],
			'1MONTH' => [],
			'3MONTH' => [],
			'6MONTH' => [],
			'YEAR' => []
		];
		
		/**
		 * [$commands description]
		 *
		 * @var array
		 */
		var $commands = [];
		
		/**
		 * [__construct description]
		 */
		public function __construct()
		{
		
		}
		
		/**
		 * [run description]
		 *
		 * @return [type] [description]
		 */
		public function run()
		{
			$this->loadScheduleFromDB();
			//create loop
			$this->createFactoryLoop();
			
			//refer this to that for dyanmic function
			$that = $this;
			//minute tick
			$this->minuteTimerBoot($that);
			//hourly tick
			$this->hourlyTimerBoot($that);
			//weekly tick
			$this->weeklyTimerBoot($that);
			//monthly tick
			$this->monthlyTimerBoot($that);
			
			//loading database every 5 minute
			$this->loop->addPeriodicTimer(
				300, function() use ( $that )
			{
				logger('Task reloading');
				$that->loadScheduleFromDB();
			}
			);
			
			//finally run the loop
			$this->loop->run();
		}
		
		/**
		 * [loadScheduleFromDB description]
		 *
		 * @return [type] [description]
		 */
		public function loadScheduleFromDB()
		{
			$this->clearTasks();
			$rows = toArray(DB::table('schedule')->select()->get());
			if ( empty($rows) )
			{
				return false;
			}
			
			foreach ( $rows as $key => $value )
			{
				array_push($this->tasks[ $value['frequency'] ], $value);
			}
			
		}
		
		/**
		 * [clearTasks description]
		 *
		 * @return [type] [description]
		 */
		public function clearTasks()
		{
			$this->tasks = $this->initial_task;
		}
		
		/**
		 * [minuteTimerBoot description]
		 *
		 * @return [type] [description]
		 */
		public function minuteTimerBoot( $that )
		{
			$tick = 1;
			$this->loop->addPeriodicTimer(
				60, function() use ( $that, &$tick )
			{
				//print_r($tasks);
				if ( !empty($that->tasks['1M']) )
				{
					//logger("Minute Schedule HeartBeat {$tick}.\n");
					$that->processTask($that->tasks['1M']);
				}
				
				if ( !empty($that->tasks['5M']) && in_array($tick, [5, 10, 15, 20, 25, 30]) )
				{
					$that->processTask($that->tasks['5M']);
				}
				
				if ( !empty($that->tasks['10M']) && in_array($tick, [10, 20, 30]) )
				{
					$that->processTask($that->tasks['10M']);
				}
				
				if ( !empty($that->tasks['15M']) && in_array($tick, [15, 30]) )
				{
					$that->processTask($that->tasks['15M']);
				}
				
				//if tick is 30 and 30m is not empty then process
				if ( !empty($that->tasks['30M']) && $tick == 30 )
				{
					$that->processTask($that->tasks['30M']);
				}
				//if tick is 30 then reset the counter
				if ( $tick == 30 )
				{
					$tick = 1;
				}
				else
				{
					$tick++;
				}
			}
			);
		}
		
		/**
		 * [hourlyTimerBoot description]
		 *
		 * @param  [type] $that [description]
		 * @return [type]       [description]
		 */
		public function hourlyTimerBoot( $that )
		{
			$tick = 1;
			$this->loop->addPeriodicTimer(
				3600, function() use ( $that, &$tick )
			{
				//print_r($tasks);
				if ( !empty($that->tasks['1H']) )
				{
					//logger("Hourly Schedule HeartBeat {$tick}.\n");
					$that->processTask($that->tasks['1H']);
				}
				
				if ( !empty($that->tasks['1D2']) && in_array($tick, [12, 24]) )
				{
					$that->processTask($that->tasks['1D2']);
				}
				//if tick is 30 and 30m is not empty then process
				if ( !empty($that->tasks['1D']) && $tick == 24 )
				{
					$that->processTask($that->tasks['1D']);
				}
				//if tick is 30 then reset the counter
				if ( $tick == 24 )
				{
					$tick = 1;
				}
				else
				{
					$tick++;
				}
			}
			);
		}
		
		/**
		 * [weeklyTimerBoot description]
		 *
		 * @param  [type] $that [description]
		 * @return [type]       [description]
		 */
		public function weeklyTimerBoot( $that )
		{
			$this->loop->addPeriodicTimer(
				604800, function() use ( $that )
			{
				//print_r($tasks);
				if ( !empty($that->tasks['1W']) )
				{
					//logger("Weekly Schedule HeartBeat.\n");
					$that->processTask($that->tasks['1W']);
				}
			}
			);
		}
		
		/**
		 * [monthlyTimerBoot description]
		 *
		 * @param  [type] $that [description]
		 * @return [type]       [description]
		 */
		public function monthlyTimerBoot( $that )
		{
			$tick = 1;
			$this->loop->addPeriodicTimer(
				2628288, function() use ( $that, &$tick )
			{
				if ( !empty($that->tasks['1MONTH']) )
				{
					//logger("Monthly Schedule HeartBeat {$tick}.\n");
					$that->processTask($that->tasks['1MONTH']);
				}
				if ( !empty($that->tasks['3MONTH']) && in_array($tick, [3, 6, 9, 12]) )
				{
					$that->processTask($that->tasks['3MONTH']);
				}
				
				if ( !empty($that->tasks['6MONTH']) && in_array($tick, [6, 12]) )
				{
					$that->processTask($that->tasks['6MONTH']);
				}
				
				if ( !empty($that->tasks['YEAR']) && $tick == 12 )
				{
					$that->processTask($that->tasks['3MONTH']);
					$tick = 1;
				}
				else
				{
					$tick++;
				}
				
			}
			);
		}
		
		/**
		 * [processTask description]
		 *
		 * @param array $tasks [description]
		 * @return [type]        [description]
		 */
		public function processTask( array $tasks )
		{
			if ( empty($tasks) )
			{
				return false;
			}
			
			foreach ( $tasks as $task )
			{
				//echo "Processing command :".$task['command']."\n";
				//commnad build
				$cmd = ['php', WEBROOT . DS . 'marwa', $task['command']];
				try
				{
					$process = new Process($cmd);
					$process->run();
					// executes after the command finishes
					if ( !$process->isSuccessful() )
					{
						throw new ProcessFailedException($process);
					}
					
					$data = [
						'name' => $task['name'],
						'executed_at' => Carbon::now()->timestamp,
						'result' => $process->getOutput()
					];
					$this->scheduleLog($data);
				} catch ( \Throwable $e )
				{
					$data = [
						'name' => $task['name'],
						'executed_at' => Carbon::now()->timestamp,
						'result' => $e
					];
					$this->scheduleLog($data);
				}
			}
		}
		
		/**
		 * [scheduleLog description]
		 *
		 * @param  [type] $data [description]
		 * @return [type]       [description]
		 */
		public function scheduleLog( $data )
		{
			try
			{
				return DB::table('schedule_logs')->insert($data)->save();
			} catch ( \Throwable $e )
			{
				logger($e);
			}
		}
		
	}
