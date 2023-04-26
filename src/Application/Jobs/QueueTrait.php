<?php
	/**
	 * @author    Mohammad Emran <memran.dhk@gmail.com>
	 * @copyright 2018
	 *
	 * @see https://www.github.com/memran
	 * @see http://www.memran.me
	 **/
	
	namespace Marwa\Application\Jobs;
	
	use Carbon\Carbon;
	use Exception;
	use Marwa\Application\Facades\DB;
	
	trait QueueTrait {
		
		/**
		 * [$queue description]
		 *
		 * @var string
		 */
		var $queue = 'default';
		/**
		 * [$priority description] 0 NORMAL/ 100 HIGH -100 LOW
		 *
		 * @var integer
		 */
		var $priority = 0;
		/**
		 * [$schedule_at description]
		 *
		 * @var null
		 */
		var $schedule_at = null;
		
		var $expect_priority = [-100, 0, 100];
		
		/**
		 * [onQueue description]
		 *
		 * @param string $queue [description]
		 * @return [type]        [description]
		 */
		public function onQueue( string $queue )
		{
			if ( empty($queue) )
			{
				throw new Exception("Queue name is empty", 1);
			}
			$this->queue = $queue;
			
			return $this;
		}
		
		/**
		 * [delay description]
		 *
		 * @param int $sec [description]
		 * @return [type]      [description]
		 */
		public function delay( int $sec )
		{
			if ( $sec < 5 )
			{
				throw new Exception('Minimum Delay is 5 seconds');
			}
			
			$this->schedule_at = Carbon::now()->addSeconds($sec)->timestamp;
			
			return $this;
		}
		
		/**
		 * [priority description]
		 *
		 * @param int|integer $priority [description]
		 * @return [type]                [description]
		 */
		public function priority( int $priority = 0 )
		{
			if ( !in_array($priority, $this->expect_priority) )
			{
				throw new Exception("Invalid Priority number", 1);
			}
			
			$this->priority = $priority;
			
			return $this;
		}
		
		/**
		 * [dispatch description]
		 *
		 * @param string $className [description]
		 * @param array $params [description]
		 * @return [type]            [description]
		 */
		public function dispatch( string $className, array $params = [] )
		{
			if ( empty($className) )
			{
				throw new Exception("Queue Handler Class name required", 1);
			}
			//check shedule is null then assign default value
			if ( is_null($this->schedule_at) )
			{
				$this->schedule_at = Carbon::now()->addSeconds(1)->timestamp;
			}
			//check class exists
			if ( !class_exists('App\Jobs\\' . $className) )
			{
				throw new Exception("Handler Class not found", 1);
			}
			
			$data = [
				'queue' => $this->queue,
				'payload' => json_encode(
					[
						'handler' => $className,
						'data' => $params
					]
				),
				'priority' => $this->priority,
				'status' => 'pending',
				'schedule_at' => $this->schedule_at,
				'created_at' => Carbon::now()->timestamp,
			];
			
			DB::table('jobs')->insert($data)->save();
		}
	}
