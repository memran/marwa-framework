<?php

	namespace Marwa\Application\Validation;
	
	use Rakit\Validation\Validator;
	
	class V extends Validator {
		
		/**
		 *
		 */
		public function setUniqueValidator()
		{
			$this->setValidator('unique', new UniqueRule());
		}
	}
