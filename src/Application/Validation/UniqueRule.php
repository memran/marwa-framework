<?php
	
	
	namespace Marwa\Application\Validation;
	
	use Marwa\Application\Facades\DB;
	
	class UniqueRule extends Rule {
		
		/**
		 * @var string
		 */
		protected $message = ":attribute is not unique";
		/**
		 * @var array
		 */
		protected $fillableParams = ['table', 'column', 'except'];
		
		
		/**
		 * @param $value
		 * @return bool
		 */
		public function check( $value ) : bool
		{
			// make sure required parameters exists
			$this->requireParameters(['table', 'column']);
			
			// getting parameters
			$column = $this->parameter('column');
			$table = $this->parameter('table');
			$except = $this->parameter('except');
			
			if ( $except and $except == $value )
			{
				return true;
			}
			/**
			 * Fetch Result from database
			 */
			$result = DB::table($table)->where($column,'=',$value)->count()->get();
			$data = reset($result);
			// true for valid, false for invalid
			return intval($data['total']) === 0;
		}
	}