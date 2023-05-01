<?php

namespace Marwa\Application\Utils;

use Nette\Utils\Arrays;

class Arr extends Arrays
{

	/**
	 * function to check given array is empty or not
	 * @@param $string $arrayName name of the array
	 * */
	public static function empty($arrAame)
	{
		if (count($arrAame) == 0)
			return true;
		else
			return false;
	}

}