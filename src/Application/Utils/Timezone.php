<?php

	namespace Marwa\Application\Utils;
	
	class Timezone {
		
		/**
		 * Timezones list with GMT offset
		 *
		 * @return array
		 * @link   http://stackoverflow.com/a/9328760
		 */
		public static function list()
		{
			$zones_array = [];
			foreach ( timezone_identifiers_list() as $key => $zone )
			{
				$zones_array[ $key ]['zone'] = $zone;
			}
			
			return $zones_array;
		}
	}
