<?php
	/**
	 * @author    Mohammad Emran <memran.dhk@gmail.com>
	 * @copyright 2018
	 *
	 * @see https://www.github.com/memran
	 * @see http://www.memran.me
	 */

	namespace Marwa\Application;

	use Exception;
	use Marwa\Application\Validation\V;

	abstract class AppController {

		/**
		 * @var V
		 */
		protected $validator;

		/**
		 * [$_validate_message description]
		 *
		 * @var array
		 */
		protected $_validate_message = [];

		/**
		 * @param $id
		 * @param $alias_name
		 */
		public function alias( $id, $alias_name )
		{
			$this->validator->setAlias($id, $alias_name);
		}

		/**
		 * @return bool
		 * @throws Exception
		 */
		public function validate()
		{
			$this->validator = new V();
			/**
			 * If there are no validation array then validate the request
			 */
			if ( empty($this->getFieldValidation()) )
			{
				return true;
			}

			if ( !empty($this->_validate_message) && is_array($this->_validate_message) )
			{
				$this->setValidationMessage($this->_validate_message);
			}

			$validation = $this->validator->make($_POST + $_FILES, $this->getFieldValidation());
			$validation->validate();
			if ( $validation->fails() )
			{
				setMessage('error', 'errors', $validation->errors()->firstOfAll());
				return false;
			}

			return true;

		}

		/**
		 * @return array
		 */
		public function getFieldValidation() : array
		{

			return [];
		}

		/**
		 * [setValidationMessage description]
		 *
		 * @param array $msg [description]
		 */
		public function setValidationMessage( array $msg )
		{
			$this->validator->setMessages($msg);
		}


		/**
		 * @param string $tplFileName
		 * @param array $data
		 * @return mixed
		 * @throws Exceptions\FileNotFoundException
		 */
		public function render( string $tplFileName, array $data = [] )
		{
			return view($tplFileName, $data);
		}

	}
