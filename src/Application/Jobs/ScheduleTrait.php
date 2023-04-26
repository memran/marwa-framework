<?php
	declare( strict_types = 1 );
	
	
	namespace Marwa\Application\Jobs;
	
	use Marwa\Application\Facades\DB;
	
	trait ScheduleTrait {
		
		/**
		 * [$frequency description]
		 *
		 * @var null
		 */
		var $frequency = null;
		/**
		 * [$command description]
		 *
		 * @var null
		 */
		var $command = null;
		
		/**
		 * [dispatch description]
		 *
		 * @return [type] [description]
		 */
		public function process()
		{
			$data = [
				'frequency' => $this->frequency,
				'command' => $this->command
			];
			
			return $this->addJob($data);
		}
		
		/**
		 * [addJob description]
		 *
		 * @param array $data [description]
		 */
		public function addJob( array $data )
		{
			if ( empty($data) )
			{
				return false;
			}
			
			return DB::table('schedule')->insert($data)->save();
		}
		
		/**
		 * [schedule description]
		 *
		 * @param string $command [description]
		 * @return [type]          [description]
		 */
		public function schedule( string $command )
		{
			$this->command = $command;
			
			return $this;
		}
		
		/**
		 * [everyMinute description]
		 *
		 * @return [type] [description]
		 */
		public function everyMinute()
		{
			$this->frequency = '1M';
			
			return $this;
		}
		
		/**
		 * [everyFiveMinutes description]
		 *
		 * @return [type] [description]
		 */
		public function everyFiveMinutes()
		{
			$this->frequency = '5M';
			
			return $this;
		}
		
		/**
		 * [everyTenMinutes description]
		 *
		 * @return [type] [description]
		 */
		public function everyTenMinutes()
		{
			$this->frequency = '10M';
			
			return $this;
		}
		
		/**
		 * [everyFifteenMinutes description]
		 *
		 * @return [type] [description]
		 */
		public function everyFifteenMinutes()
		{
			$this->frequency = '15M';
			
			return $this;
		}
		
		/**
		 * [everyThirtyMinutes description]
		 *
		 * @return [type] [description]
		 */
		public function everyThirtyMinutes()
		{
			$this->frequency = '30M';
			
			return $this;
		}
		
		/**
		 * [hourly description]
		 *
		 * @return [type] [description]
		 */
		public function hourly()
		{
			$this->frequency = '1H';
			
			return $this;
		}
		
		/**
		 * [daily description]
		 *
		 * @return [type] [description]
		 */
		public function daily()
		{
			$this->frequency = '1D';
			
			return $this;
		}
		
		/**
		 * [twiceDaily description]
		 *
		 * @return [type] [description]
		 */
		public function twiceDaily()
		{
			$this->frequency = '1D2';
			
			return $this;
		}
		
		/**
		 * [weekly description]
		 *
		 * @return [type] [description]
		 */
		public function weekly()
		{
			$this->frequency = '1W';
			
			return $this;
		}
		
		/**
		 * [monthly description]
		 *
		 * @return [type] [description]
		 */
		public function monthly()
		{
			$this->frequency = '1MONTH';
			
			return $this;
		}
		
		/**
		 * [yearly description]
		 *
		 * @return [type] [description]
		 */
		public function yearly()
		{
			$this->frequency = '12MONTH';
			
			return $this;
		}
		
		/**
		 * [quarterly description]
		 *
		 * @return [type] [description]
		 */
		public function quarterly()
		{
			$this->frequency = '3MONTH';
			
			return $this;
		}
		
		/**
		 * [halfyearly description]
		 *
		 * @return [type] [description]
		 */
		public function halfyearly()
		{
			$this->frequency = '6MONTH';
			
			return $this;
		}
	}

